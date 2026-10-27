<?php
/**
 * Retry Manager Engine
 * Handles rule precedence, price resolution, atomic locking, dry-run simulation, and payment execution.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/KonnektiveService.php';
require_once __DIR__ . '/CampaignManager.php';
require_once __DIR__ . '/AuditLogger.php';
require_once __DIR__ . '/AffiliateManager.php';

class RetryManager {
    private PDO $db;
    private KonnektiveService $api;
    private CampaignManager $campaignManager;
    private AffiliateManager $affiliateManager;
    private array $config;

    public function __construct(?KonnektiveService $api = null, ?CampaignManager $campaignManager = null, ?AffiliateManager $affiliateManager = null) {
        $this->db = Database::getInstance()->getConnection();
        $this->config = require __DIR__ . '/../config/config.php';
        $this->api = $api ?: new KonnektiveService();
        $this->campaignManager = $campaignManager ?: new CampaignManager($this->api);
        $this->affiliateManager = $affiliateManager ?: new AffiliateManager($this->api);

        // Ensure crm_status and crm_verified_at columns exist
        try {
            $this->db->exec("ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `crm_status` VARCHAR(32) NOT NULL DEFAULT 'SYNCED' AFTER `is_retry_created_order`");
            $this->db->exec("ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `crm_verified_at` DATETIME NULL AFTER `crm_status`");
        } catch (Throwable $ignored) {}
    }

    public function getAffiliateManager(): AffiliateManager {
        return $this->affiliateManager;
    }

    /**
     * Resolves the target charge price and rule source based on strict 5-tier precedence:
     * 1. Explicit override for the individual pending attempt.
     * 2. Offer-specific campaign-product override (`offer_campaign_products.custom_retry_price`).
     * 3. Shared campaign-product override (`campaign_products.custom_retry_price`).
     * 4. Campaign-level override (`campaigns.retry_price_override`).
     * 5. Original order amount (`orders.original_amount`).
     */
    public function resolvePriceDetailed(array $order, ?float $pendingAttemptOverride = null): array {
        // 1. Explicit override for individual pending attempt
        if ($pendingAttemptOverride !== null && $pendingAttemptOverride > 0) {
            return [
                'price' => round($pendingAttemptOverride, 2),
                'rule_source' => 'ATTEMPT_OVERRIDE',
                'description' => 'Explicit manual attempt override'
            ];
        }

        $campId = (int)($order['campaign_id'] ?? 0);
        $prodId = (int)($order['product_id'] ?? 0);
        $offerId = trim((string)($order['offer_id'] ?? ''));

        // 2. Offer-specific campaign-product override
        if (!empty($offerId) && $campId > 0 && $prodId > 0) {
            $ocpStmt = $this->db->prepare("
                SELECT ocp.custom_retry_price 
                FROM offer_campaign_products ocp
                LEFT JOIN campaign_products cp ON cp.campaign_id = ocp.campaign_id AND cp.product_id = ocp.product_id
                JOIN offers o ON o.offer_id = ocp.offer_id
                WHERE ocp.offer_id = :oid AND ocp.campaign_id = :cid 
                  AND (ocp.product_id = :pid OR cp.campaign_product_id = :pid) 
                  AND ocp.is_enabled = 1 
                  AND o.use_custom_pricing = 1 
                  AND o.is_active = 1
                LIMIT 1
            ");
            $ocpStmt->execute([':oid' => $offerId, ':cid' => $campId, ':pid' => $prodId]);
            $ocp = $ocpStmt->fetch();
            if ($ocp && $ocp['custom_retry_price'] !== null && (float)$ocp['custom_retry_price'] > 0) {
                return [
                    'price' => round((float)$ocp['custom_retry_price'], 2),
                    'rule_source' => 'OFFER_CAMPAIGN_PRODUCT_OVERRIDE',
                    'description' => "Offer-specific override for '{$offerId}' (Campaign #{$campId}, Product #{$prodId})"
                ];
            }
        }

        // 3. Shared campaign-product override
        if ($campId > 0 && $prodId > 0) {
            $cpStmt = $this->db->prepare("
                SELECT custom_retry_price 
                FROM campaign_products 
                WHERE campaign_id = :cid AND (product_id = :pid OR campaign_product_id = :pid) AND is_enabled = 1 
                LIMIT 1
            ");
            $cpStmt->execute([':cid' => $campId, ':pid' => $prodId]);
            $cp = $cpStmt->fetch();
            if ($cp && $cp['custom_retry_price'] !== null && (float)$cp['custom_retry_price'] > 0) {
                return [
                    'price' => round((float)$cp['custom_retry_price'], 2),
                    'rule_source' => 'SHARED_CAMPAIGN_PRODUCT_OVERRIDE',
                    'description' => "Shared campaign-product override (Campaign #{$campId}, Product #{$prodId})"
                ];
            }
        }

        // 4. Campaign-level override
        if ($campId > 0) {
            $cStmt = $this->db->prepare("SELECT retry_price_override FROM campaigns WHERE campaign_id = :cid AND is_enabled = 1 LIMIT 1");
            $cStmt->execute([':cid' => $campId]);
            $camp = $cStmt->fetch();
            if ($camp && !empty($camp['retry_price_override']) && (float)$camp['retry_price_override'] > 0) {
                return [
                    'price' => round((float)$camp['retry_price_override'], 2),
                    'rule_source' => 'CAMPAIGN_OVERRIDE',
                    'description' => "Campaign-level override (Campaign #{$campId})"
                ];
            }
        }

        // 5. Fallback to original order amount
        $orig = (float)($order['original_amount'] ?? 0.0);
        return [
            'price' => $orig > 0 ? round($orig, 2) : 0.0,
            'rule_source' => 'ORIGINAL_ORDER_AMOUNT',
            'description' => 'Original order amount on decline'
        ];
    }

    public function resolvePrice(array $order, ?float $pendingAttemptOverride = null): float {
        $detailed = $this->resolvePriceDetailed($order, $pendingAttemptOverride);
        return $detailed['price'];
    }

    /**
     * Checks if the decline reason is retryable (soft decline) vs terminal (hard decline)
     */
    public function isDeclineRetryable(string $declineReason): bool {
        $reasonLower = strtolower(trim($declineReason));
        $policy = $this->config['retry_policy'];

        // Hard decline check
        foreach ($policy['hard_decline_keywords'] as $hardKw) {
            if (strpos($reasonLower, $hardKw) !== false) {
                return false;
            }
        }

        // Soft decline / retryable check
        foreach ($policy['retryable_decline_keywords'] as $softKw) {
            if (strpos($reasonLower, $softKw) !== false) {
                return true;
            }
        }

        // If unknown, default to retryable once unless hard decline pattern is found
        return true;
    }

    /**
     * Decrypts encrypted card data (AES-256-CBC)
     */
    public function decryptCard(string $encryptedMessage): ?string {
        try {
            $encryptionKey = $this->config['app']['card_encryption_key'] ?? '1~3';
            $combinedData = base64_decode($encryptedMessage);
            $ivLength = openssl_cipher_iv_length('aes-256-cbc');
            if (strlen($combinedData) <= $ivLength) {
                return null;
            }
            $receivedIv = substr($combinedData, 0, $ivLength);
            $receivedEncryptedData = substr($combinedData, $ivLength);
            $decrypted = openssl_decrypt($receivedEncryptedData, 'aes-256-cbc', $encryptionKey, 0, $receivedIv);
            return $decrypted !== false ? $decrypted : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Encrypts card data (AES-256-CBC)
     */
    public function encryptCard(string $data): string {
        $encryptionKey = $this->config['app']['card_encryption_key'] ?? '1~3';
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $encryptionKey, 0, $iv);
        return base64_encode($iv . $encryptedData);
    }

    /**
     * Comprehensive multi-attribute evaluation of order eligibility:
     * - Payment Credential Availability: Customer card captured and encrypted at checkout.
     * - General Retry Eligibility: Valid captured card, soft decline, enabled campaign & products, within attempt limits, not paid/canceled, not excluded test order.
     * - Custom Price Eligibility: Valid pricing rule, captured card, supported package configuration.
     * - Due Now: Eligible, scheduled, next attempt timestamp <= NOW(), not paused, not processing.
     * - Test Order Detection: Reliable Konnektive transaction/merchant indicators.
     * - Explicit Blocking Reasons: Returns every single applicable blocking reason.
     */
    public function evaluateOrderComprehensive(array $order): array {
        $blockingReasons = [];
        $campId = (int)($order['campaign_id'] ?? 0);
        $prodId = (int)($order['product_id'] ?? 0);
        $prodIdsJson = $order['product_ids_json'] ?? null;
        $orderId = trim((string)($order['order_id'] ?? ''));

        // 0. Recursive Retry Prevention & Affiliate Mapping Integrity:
        // Existing retry orders must not enter the workflow as unrelated original declines (e.g. no R_R_Emerald).
        $isRetryCreated = !empty($order['is_retry_created_order'])
            || $this->affiliateManager->isRetryAffiliate($order['source_title'] ?? '')
            || $this->affiliateManager->isRetryAffiliate($order['aff_id'] ?? '');

        if ($isRetryCreated) {
            $blockingReasons[] = "Existing retry-created orders cannot enter the retry workflow as original declines.";
        }

        // Affiliate Mapping Verification:
        // Dynamically resolve original affiliate ID to existing R_ counterpart
        $affId = trim((string)($order['aff_id'] ?? ''));
        $sourceTitle = trim((string)($order['source_title'] ?? ''));
        $mapping = null;

        if (!$isRetryCreated) {
            if ($affId !== '' || $sourceTitle !== '') {
                $mapping = $this->affiliateManager->getMappingForAffiliate($affId ?: $sourceTitle);
                if ($mapping === null) {
                    $blockingReasons[] = "Missing verified R_ affiliate mapping for '" . ($sourceTitle ?: $affId) . "'.";
                } elseif (strtolower($mapping['status'] ?? '') === 'ambiguous') {
                    $blockingReasons[] = "Ambiguous R_ affiliate mapping for '" . ($sourceTitle ?: $affId) . "'. Requires administrative review.";
                } elseif (!in_array(strtolower($mapping['status'] ?? ''), ['active', 'verified'], true)) {
                    $blockingReasons[] = "Unverified R_ affiliate mapping for '" . ($sourceTitle ?: $affId) . "' (status: {$mapping['status']}).";
                }
            } else {
                $blockingReasons[] = "Missing affiliate attribution on order (no affiliate ID or source title).";
            }
        }

        // 1. Payment Credential Check:
        // A new order can be created at custom manual price if card details were captured at checkout (`cvv_data`).
        $hasCapturedCard = !empty($order['cvv_data']);
        $hasPaymentRef = $hasCapturedCard || (!empty($orderId) && (!isset($order['has_payment_reference']) || (int)$order['has_payment_reference'] === 1));
        if (!$hasCapturedCard) {
            $blockingReasons[] = "Missing captured card details from checkout. Custom-price new order rerun requires card captured at checkout.";
        }

        // 2. Transaction / Payment Status Check: Don't charge already paid or canceled orders
        $txStatus = strtoupper(trim($order['transaction_status'] ?? 'DECLINED'));
        $retryStatus = strtolower(trim($order['retry_status'] ?? 'pending'));
        if (in_array($txStatus, ['COMPLETE', 'REFUNDED', 'CANCELED'], true) || $retryStatus === 'successful') {
            $blockingReasons[] = "Already paid or canceled.";
        }

        // 3. Awaiting reconciliation check
        if ($retryStatus === 'needs_review' && strpos(strtolower($order['eligibility_reason'] ?? ''), 'timeout') !== false) {
            $blockingReasons[] = "Previous payment outcome awaiting reconciliation.";
        }

        // 4. Test Order Check: Test orders are excluded from live retry execution
        $testStatus = strtolower(trim($order['test_order_status'] ?? 'live'));
        if ($testStatus === 'test') {
            $blockingReasons[] = "Test order excluded from live execution.";
        }

        // 5. Hard Decline Check
        $declineReason = trim($order['decline_reason'] ?? 'Declined');
        if (!$this->isDeclineRetryable($declineReason)) {
            $blockingReasons[] = "Non-retryable decline (Hard Decline: {$declineReason}).";
        }

        // 6. Max Attempts Check
        $currentAttempts = (int)($order['attempt_count'] ?? 0);
        $maxAttempts = (int)($this->config['retry_policy']['max_attempts'] ?? 3);

        // 7. Campaign & Product Allowlist & Rules (delegated to CampaignManager)
        $campElig = $this->campaignManager->evaluateOrderEligibility($order);
        if (!$campElig['eligible']) {
            $blockingReasons[] = $campElig['reason'];
        } else {
            $maxAttempts = (int)($campElig['campaign']['max_attempts'] ?? $maxAttempts);
        }

        if ($currentAttempts >= $maxAttempts) {
            $blockingReasons[] = "Maximum attempts reached ({$currentAttempts}/{$maxAttempts}).";
        }

        // 8. Custom Price Eligibility Check
        // Custom price requires: captured card + campaign/product eligible + single product package (or explicit whole-order pricing)
        $productIds = [];
        if (!empty($prodIdsJson)) {
            $decoded = json_decode($prodIdsJson, true);
            if (is_array($decoded)) $productIds = array_map('intval', $decoded);
        }
        if (empty($productIds) && $prodId > 0) {
            $productIds = [$prodId];
        }

        $customPriceEligible = $hasCapturedCard && $campElig['eligible'] && ($order['custom_price_eligible'] ?? 1) == 1;
        if (count($productIds) > 1 && empty($order['planned_price'])) {
            $customPriceEligible = false;
        }

        // Determine Overall Status Code & Primary Reason by logical precedence
        $isRetryEligible = count($blockingReasons) === 0;
        $status = 'ELIGIBLE';
        $primaryReason = 'Order is verified eligible for separate retry order.';

        if (in_array("Already paid or canceled.", $blockingReasons, true)) {
            $status = 'ALREADY_PAID_OR_CANCELED';
            $primaryReason = 'Order already successfully completed or canceled.';
        } elseif ($testStatus === 'test') {
            $status = 'TEST_ORDER_EXCLUDED';
            $primaryReason = 'Test order excluded from live execution.';
        } elseif (!$this->isDeclineRetryable($declineReason)) {
            $status = 'BLOCKED_HARD_DECLINE';
            $primaryReason = "Non-retryable decline ({$declineReason}).";
        } elseif (empty($orderId)) {
            $status = 'MISSING_PAYMENT_REF';
            $primaryReason = 'Missing reusable payment reference.';
        } elseif ($currentAttempts >= $maxAttempts) {
            $status = 'BLOCKED_MAX_ATTEMPTS';
            $primaryReason = "Maximum attempt limit ({$maxAttempts}) reached.";
        } elseif (!$campElig['eligible']) {
            $status = $campElig['status'];
            $primaryReason = $campElig['reason'];
        } elseif ($isRetryCreated) {
            $status = 'BLOCKED_RETRY_CREATED_ORDER';
            $primaryReason = 'Existing retry-created orders cannot enter the retry workflow.';
        } elseif ($mapping === null && ($affId !== '' || $sourceTitle !== '')) {
            $status = 'BLOCKED_MISSING_RETRY_AFFILIATE';
            $primaryReason = "Missing verified R_ affiliate mapping for '" . ($sourceTitle ?: $affId) . "'.";
        } elseif ($mapping && strtolower($mapping['status'] ?? '') === 'ambiguous') {
            $status = 'BLOCKED_AMBIGUOUS_RETRY_AFFILIATE';
            $primaryReason = "Ambiguous R_ affiliate mapping for '" . ($sourceTitle ?: $affId) . "'. Requires administrative review.";
        } elseif ($mapping && !in_array(strtolower($mapping['status'] ?? ''), ['active', 'verified'], true)) {
            $status = 'BLOCKED_UNVERIFIED_RETRY_AFFILIATE';
            $primaryReason = "Unverified R_ affiliate mapping for '" . ($sourceTitle ?: $affId) . "' (status: {$mapping['status']}).";
        } elseif (in_array("Missing affiliate attribution on order (no affiliate ID or source title).", $blockingReasons, true)) {
            $status = 'BLOCKED_MISSING_AFFILIATE';
            $primaryReason = 'Order lacks affiliate identification for R_ mapping.';
        } elseif (!$hasCapturedCard) {
            $status = 'MISSING_CAPTURED_CARD';
            $primaryReason = 'Missing captured card details from checkout. Custom-price new order rerun requires card captured at checkout.';
        } elseif (in_array("Previous payment outcome awaiting reconciliation.", $blockingReasons, true)) {
            $status = 'AWAITING_RECONCILIATION';
            $primaryReason = 'Previous payment outcome awaiting reconciliation.';
        }


        // Ready to retry now?
        $isDueNow = $isRetryEligible && 
                    $retryStatus === 'scheduled' && 
                    (empty($order['next_attempt_at']) || strtotime($order['next_attempt_at']) <= time()) && 
                    (empty($order['is_paused']) || (int)$order['is_paused'] === 0) &&
                    $retryStatus !== 'processing';

        return [
            'eligible' => $isRetryEligible,
            'has_payment_reference' => $hasPaymentRef,
            'is_custom_price_eligible' => $customPriceEligible,
            'is_due_now' => $isDueNow,
            'is_test_order' => $testStatus === 'test',
            'test_order_status' => $testStatus,
            'is_retry_created_order' => $isRetryCreated,
            'affiliate_mapping' => $mapping,
            'original_aff_display' => $this->affiliateManager->resolveAffiliateDisplay($affId ?: null, $sourceTitle ?: null),
            'retry_aff_display' => $mapping ? $this->affiliateManager->resolveAffiliateDisplay($mapping['retry_aff_id'], $mapping['retry_title']) : null,
            'status' => $status,
            'reason' => $primaryReason,
            'blocking_reasons' => $blockingReasons,
            'campaign' => $campElig['campaign'] ?? null,
            'attempts_remaining' => max(0, $maxAttempts - $currentAttempts),
            'max_attempts' => $maxAttempts
        ];
    }

    /**
     * Schedules the next retry attempt for an order with comprehensive validation.
     */
    public function scheduleRetry(string $orderId, ?int $delayMinutes = null, ?float $customPrice = null, ?string $scheduledBy = null): array {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE order_id = :oid LIMIT 1");
        $stmt->execute([':oid' => $orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            return ['success' => false, 'message' => "Order {$orderId} not found."];
        }

        // Comprehensive eligibility check
        $eval = $this->evaluateOrderComprehensive($order);
        if (!$eval['eligible']) {
            $upd = $this->db->prepare("
                UPDATE orders 
                SET retry_eligibility = :el, eligibility_reason = :rs, retry_status = 'paused', updated_at = NOW() 
                WHERE order_id = :oid
            ");
            $upd->execute([':el' => $eval['status'], ':rs' => $eval['reason'], ':oid' => $orderId]);
            return ['success' => false, 'message' => $eval['reason'], 'status' => $eval['status']];
        }

        $currentAttempts = (int)$order['attempt_count'];
        $nextAttemptNum = $currentAttempts + 1;
        $delay = $delayMinutes ?? (int)($eval['campaign']['retry_delay_minutes'] ?? $this->config['retry_policy']['default_delay_minutes']);
        $scheduledAt = date('Y-m-d H:i:s', strtotime("+{$delay} minutes"));
        $priceInfo = $this->resolvePriceDetailed($order, $customPrice);
        $plannedPrice = $priceInfo['price'];
        $ruleSource = $priceInfo['rule_source'];

        $mapping = $eval['affiliate_mapping'] ?? null;
        $origAffId = $order['aff_id'] ?? null;
        $origAffName = $order['source_title'] ?? ($mapping['original_title'] ?? null);
        $retryAffId = $mapping['retry_aff_id'] ?? null;
        $retryAffName = $mapping['retry_title'] ?? null;

        // Custom price capability check
        $isCustomPrice = abs($plannedPrice - (float)$order['original_amount']) > 0.01;
        if ($isCustomPrice && !$eval['is_custom_price_eligible']) {
            return [
                'success' => false, 
                'message' => 'Custom pricing unsupported for this multi-product order without explicit bundle pricing.',
                'status' => 'CUSTOM_PRICE_UNSUPPORTED'
            ];
        }

        $this->db->beginTransaction();
        try {
            // Update order record
            $ordUpd = $this->db->prepare("
                UPDATE orders 
                SET retry_status = 'scheduled',
                    next_attempt_at = :nxt,
                    planned_price = :prc,
                    pricing_rule_source = :src,
                    retry_aff_id = :raid,
                    retry_aff_name = :raname,
                    retry_eligibility = :el,
                    eligibility_reason = CONCAT('Scheduled for attempt ', :att, ' via ', :src, ' [R_ Affiliate: ', COALESCE(:raname2, 'None'), ']'),
                    updated_at = NOW()
                WHERE order_id = :oid
            ");
            $ordUpd->execute([
                ':nxt' => $scheduledAt,
                ':prc' => $plannedPrice,
                ':src' => $ruleSource,
                ':raid' => $retryAffId,
                ':raname' => $retryAffName,
                ':raname2' => $retryAffName,
                ':el' => $eval['status'],
                ':att' => $nextAttemptNum,
                ':oid' => $orderId
            ]);

            // Insert planned attempt record with attribution snapshot
            $attIns = $this->db->prepare("
                INSERT INTO retry_attempts (
                    order_id, attempt_number, scheduled_at, planned_price, pricing_rule_source, 
                    status, trigger_type, triggered_by, original_aff_id, original_aff_name, 
                    retry_aff_id, retry_aff_name, submitted_source_value1, created_at, updated_at
                ) VALUES (
                    :oid, :att, :sch, :prc, :src, 
                    'scheduled', :trig, :by, :oaff, :oaffname, 
                    :raff, :raffname, 'retry_order', NOW(), NOW()
                )
            ");
            $attIns->execute([
                ':oid' => $orderId,
                ':att' => $nextAttemptNum,
                ':sch' => $scheduledAt,
                ':prc' => $plannedPrice,
                ':src' => $ruleSource,
                ':trig' => $scheduledBy ? 'manual' : 'cron',
                ':by' => $scheduledBy ?: 'system',
                ':oaff' => $origAffId,
                ':oaffname' => $origAffName,
                ':raff' => $retryAffId,
                ':raffname' => $retryAffName
            ]);

            AuditLogger::log('retry_scheduled', 'order', $orderId, [
                'attempt_number' => $nextAttemptNum,
                'scheduled_at' => $scheduledAt,
                'planned_price' => $plannedPrice,
                'rule_source' => $ruleSource,
                'original_affiliate' => "{$origAffName} ({$origAffId})",
                'retry_affiliate' => "{$retryAffName} ({$retryAffId})",
                'scheduled_by' => $scheduledBy ?: 'system'
            ], $scheduledBy);

            $this->db->commit();
            return [
                'success' => true,
                'attempt_number' => $nextAttemptNum,
                'scheduled_at' => $scheduledAt,
                'planned_price' => $plannedPrice
            ];
        } catch (Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Scheduling failed: ' . $e->getMessage()];
        }
    }

    /**
     * Executes a retry attempt for an order with atomic locking and dry-run safety.
     * Enforces campaign, product, test order, and decline restrictions server-side
     * for BOTH manual and automated retries.
     */
    public function executeRetry(string $orderId, bool $isManual = false, ?string $executedBy = null, ?float $overridePrice = null, ?bool $forceDryRun = null, ?string $preferredMode = null): array {
        // Atomic lock using database transaction
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE order_id = :oid FOR UPDATE");
            $stmt->execute([':oid' => $orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $this->db->rollBack();
                return ['success' => false, 'message' => "Order {$orderId} not found."];
            }

            // If this is a rerun order created from a retry attempt, seamlessly route retry execution to its parent order!
            if ((int)($order['is_retry_created_order'] ?? 0) === 1) {
                $parentOrderId = null;
                $pStmt = $this->db->prepare("SELECT order_id FROM orders WHERE retry_order_id = :roid AND is_retry_created_order = 0 LIMIT 1");
                $pStmt->execute([':roid' => $orderId]);
                $parentOrderId = $pStmt->fetchColumn();

                if (!$parentOrderId) {
                    $attStmt = $this->db->prepare("SELECT order_id FROM retry_attempts WHERE konnektive_order_id = :roid ORDER BY id DESC LIMIT 1");
                    $attStmt->execute([':roid' => $orderId]);
                    $parentOrderId = $attStmt->fetchColumn();
                }

                if (!$parentOrderId) {
                    try {
                        $crmOrd = $this->api->request('order/query/', ['orderId' => $orderId]);
                        $co = $crmOrd['message']['data'][0] ?? [];
                        if (!empty($co['custom2'])) {
                            $parentOrderId = trim($co['custom2']);
                        } elseif (!empty($co['custom1']) && preg_match('/retry_of_([A-Za-z0-9_-]+)/', $co['custom1'], $m)) {
                            $parentOrderId = $m[1];
                        }
                    } catch (Throwable $e) {}
                }

                if ($parentOrderId && $parentOrderId !== $orderId) {
                    $this->db->rollBack();
                    return $this->executeRetry($parentOrderId, $isManual, $executedBy, $overridePrice, $forceDryRun, $preferredMode);
                }
            }

            // Guard: stop if already successful or processing
            if ($order['retry_status'] === 'successful' || $order['transaction_status'] === 'COMPLETE') {
                $this->db->rollBack();
                return ['success' => false, 'message' => "Order {$orderId} is already completed/successful."];
            }

            if ($order['retry_status'] === 'processing') {
                $this->db->rollBack();
                return ['success' => false, 'message' => "Order {$orderId} is currently being processed by another worker."];
            }

            $isNormalRerunMode = ($preferredMode === 'normal');
            // Re-validate complete eligibility immediately before charging - for BOTH manual and automated!
            $eval = $this->evaluateOrderComprehensive($order);
            if (!$eval['eligible']) {
                // If the only blocker is MISSING_CAPTURED_CARD or unknown offer, but this is a valid declined CRM order:
                // Allow Normal Konnektive Rerun fallback!
                if (($eval['status'] === 'MISSING_CAPTURED_CARD' || strpos($eval['status'], 'UNKNOWN_OFFER') !== false) && !empty($order['order_id']) && $eval['status'] !== 'TEST_ORDER_EXCLUDED') {
                    $isNormalRerunMode = true;
                } else {
                    $this->db->prepare("
                        UPDATE orders 
                        SET retry_eligibility = :el, eligibility_reason = :rs, retry_status = 'paused', updated_at = NOW() 
                        WHERE order_id = :oid
                    ")->execute([':el' => $eval['status'], ':rs' => $eval['reason'], ':oid' => $orderId]);

                    $this->db->commit();
                    return ['success' => false, 'message' => 'Attempt blocked: ' . $eval['reason'], 'status' => $eval['status'], 'blocking_reasons' => $eval['blocking_reasons']];
                }
            }

            // Mark order as processing
            $this->db->prepare("UPDATE orders SET retry_status = 'processing', updated_at = NOW() WHERE order_id = :oid")
                     ->execute([':oid' => $orderId]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Failed to acquire order lock: ' . $e->getMessage()];
        }

        // Check global dry-run mode and live retry switch
        $settingsStmt = $this->db->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('global_live_retries_enabled', 'dry_run_mode')");
        $settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $globalLive = (int)($settings['global_live_retries_enabled'] ?? 0) === 1;
        $dryRunSetting = (int)($settings['dry_run_mode'] ?? 1) === 1 || !$globalLive;
        $dryRun = $forceDryRun !== null ? $forceDryRun : ($dryRunSetting || $executedBy === 'automated_test');

        $priceInfo = $this->resolvePriceDetailed($order, $overridePrice);
        $targetPrice = $priceInfo['price'];
        $ruleSource = $priceInfo['rule_source'];
        $attemptNum = (int)$order['attempt_count'] + 1;

        // Extract verified R_ affiliate mapping for attribution & pixel suppression
        $mapping = $eval['affiliate_mapping'] ?? null;
        $origAffId = $order['aff_id'] ?? null;
        $origAffName = $order['source_title'] ?? ($mapping['original_title'] ?? null);
        $retryAffId = $mapping['retry_aff_id'] ?? null;
        $retryAffName = $mapping['retry_title'] ?? null;
        $submittedSv1 = 'retry_order'; // Suppresses original affiliate click IDs/pixels

        // Create or update attempt row
        $attemptId = null;
        $findAtt = $this->db->prepare("SELECT id FROM retry_attempts WHERE order_id = :oid AND status = 'scheduled' ORDER BY id DESC LIMIT 1");
        $findAtt->execute([':oid' => $orderId]);
        $existingAtt = $findAtt->fetch();

        if ($existingAtt) {
            $attemptId = $existingAtt['id'];
            $this->db->prepare("
                UPDATE retry_attempts 
                SET status = 'processing', executed_at = NOW(), planned_price = :prc, pricing_rule_source = :src, 
                    is_dry_run = :dr, original_aff_id = :oaff, original_aff_name = :oaffname, 
                    retry_aff_id = :raff, retry_aff_name = :raffname, submitted_source_value1 = :ssv1
                WHERE id = :id
            ")->execute([
                ':prc' => $targetPrice, 
                ':src' => $ruleSource, 
                ':dr' => $dryRun ? 1 : 0, 
                ':oaff' => $origAffId,
                ':oaffname' => $origAffName,
                ':raff' => $retryAffId,
                ':raffname' => $retryAffName,
                ':ssv1' => $submittedSv1,
                ':id' => $attemptId
            ]);
        } else {
            $insAtt = $this->db->prepare("
                INSERT INTO retry_attempts (
                    order_id, attempt_number, scheduled_at, executed_at, planned_price, pricing_rule_source, 
                    status, trigger_type, triggered_by, is_dry_run, original_aff_id, original_aff_name, 
                    retry_aff_id, retry_aff_name, submitted_source_value1, created_at, updated_at
                ) VALUES (
                    :oid, :att, NOW(), NOW(), :prc, :src, 
                    'processing', :trig, :by, :dr, :oaff, :oaffname, 
                    :raff, :raffname, :ssv1, NOW(), NOW()
                )
            ");
            $insAtt->execute([
                ':oid' => $orderId,
                ':att' => $attemptNum,
                ':prc' => $targetPrice,
                ':src' => $ruleSource,
                ':trig' => $isManual ? 'manual' : 'cron',
                ':by' => $executedBy ?: 'system',
                ':dr' => $dryRun ? 1 : 0,
                ':oaff' => $origAffId,
                ':oaffname' => $origAffName,
                ':raff' => $retryAffId,
                ':raffname' => $retryAffName,
                ':ssv1' => $submittedSv1
            ]);
            $attemptId = $this->db->lastInsertId();
        }

        // =====================================================================
        // DRY RUN EXECUTION
        // =====================================================================
        if ($dryRun) {
            $simulatedOrderId = 'DRY_ORD_' . $orderId . '_' . time();
            $simulatedMsg = "DRY-RUN SIMULATION: Separate retry order evaluated for Order #{$orderId}. Attributed to {$retryAffName} ({$retryAffId}) [Original: {$origAffName} ({$origAffId})] at target price \${$targetPrice} (Rule: {$ruleSource}). Original affiliate pixels suppressed. (Live payments disabled).";
            
            $this->db->prepare("
                UPDATE retry_attempts 
                SET status = 'dry_run_completed', konnektive_order_id = :roid, response_message = :msg, updated_at = NOW() 
                WHERE id = :id
            ")->execute([':roid' => $simulatedOrderId, ':msg' => $simulatedMsg, ':id' => $attemptId]);

            $this->db->prepare("
                UPDATE orders 
                SET retry_status = 'pending', retry_order_id = :roid, attempt_count = :att, last_attempt_at = NOW(), 
                    planned_price = :prc, pricing_rule_source = :src, retry_aff_id = :raid, 
                    retry_aff_name = :raname, updated_at = NOW() 
                WHERE order_id = :oid
            ")->execute([
                ':roid' => $simulatedOrderId,
                ':att' => $attemptNum, 
                ':prc' => $targetPrice, 
                ':src' => $ruleSource, 
                ':raid' => $retryAffId,
                ':raname' => $retryAffName,
                ':oid' => $orderId
            ]);

            AuditLogger::log('retry_dry_run', 'order', $orderId, [
                'attempt_id' => $attemptId,
                'retry_order_id' => $simulatedOrderId,
                'planned_price' => $targetPrice,
                'rule_source' => $ruleSource,
                'original_affiliate' => "{$origAffName} ({$origAffId})",
                'retry_affiliate' => "{$retryAffName} ({$retryAffId})",
                'pixel_suppression' => 'Attributed to R_ affiliate; original tokens omitted',
                'executed_by' => $executedBy ?: 'system'
            ], $executedBy);

            return [
                'success' => true,
                'is_dry_run' => true,
                'order_id' => $simulatedOrderId,
                'target_retry_aff_id' => $retryAffId,
                'message' => $simulatedMsg,
                'planned_price' => $targetPrice,
                'rule_source' => $ruleSource,
                'retry_affiliate' => "{$retryAffName} ({$retryAffId})"
            ];
        }


        // =====================================================================
        // LIVE PAYMENT EXECUTION VIA KONNEKTIVE CRM
        // Creates a separate new order via Konnektive's /order/import/ endpoint
        // using decrypted captured card credentials at the exact custom/manual price.
        // Automatically re-attributes to the verified R_ affiliate ID and
        // suppresses original affiliate click tracking tokens.
        // Links the new order back to the parent order in CRM and database.
        // =====================================================================

        // -----------------------------------------------------------------
        // STEP 1: PRE-FLIGHT CHECK IN KONNEKTIVE CRM
        // Prevent duplicate charges: If order is already COMPLETE, sync and finish!
        // -----------------------------------------------------------------
        $crmOrder = null;
        try {
            $crmOrder = $this->api->getOrder($orderId);
        } catch (Throwable $e) {
            // CRM network hiccup handled below
        }

        if (!empty($crmOrder)) {
            $crmStatus = strtoupper(trim((string)($crmOrder['orderStatus'] ?? '')));
            if ($crmStatus === 'COMPLETE') {
                $totalAmt = (float)($crmOrder['totalAmount'] ?? $order['original_amount']);
                $crmAffId = !empty($crmOrder['affId']) ? $crmOrder['affId'] : $retryAffId;
                $crmSourceTitle = !empty($crmOrder['sourceTitle']) ? $crmOrder['sourceTitle'] : $retryAffName;

                $this->db->prepare("
                    UPDATE retry_attempts 
                    SET status = 'successful', charged_price = :prc, pricing_rule_source = 'CRM_VERIFIED_COMPLETE', 
                        konnektive_order_id = :koid, response_message = 'Order is already marked COMPLETE in Konnektive CRM.', updated_at = NOW() 
                    WHERE id = :id
                ")->execute([
                    ':prc' => $totalAmt,
                    ':koid' => $orderId,
                    ':id' => $attemptId
                ]);

                $this->db->prepare("
                    UPDATE orders 
                    SET retry_status = 'successful',
                        transaction_status = 'COMPLETE',
                        retry_eligibility = 'COMPLETED',
                        eligibility_reason = 'Order already completed and approved in Konnektive CRM.',
                        aff_id = COALESCE(:aff, aff_id),
                        source_title = COALESCE(:stitle, source_title),
                        last_attempt_at = NOW(),
                        next_attempt_at = NULL,
                        updated_at = NOW() 
                    WHERE order_id = :oid
                ")->execute([
                    ':aff' => $crmAffId,
                    ':stitle' => $crmSourceTitle,
                    ':oid' => $orderId
                ]);

                AuditLogger::log('retry_success', 'order', $orderId, [
                    'attempt_id' => $attemptId,
                    'note' => 'Order verified already complete in Konnektive CRM; status synchronized without duplicate charge',
                    'total_amount' => $totalAmt,
                    'affId' => $crmAffId,
                    'sourceTitle' => $crmSourceTitle
                ], $executedBy);

                return [
                    'success' => true,
                    'status' => 'SUCCESS',
                    'already_completed' => true,
                    'message' => "Order #{$orderId} is already marked COMPLETE in Konnektive CRM. Successfully updated status to completed.",
                    'charged_price' => $totalAmt,
                    'konnektive_order_id' => $orderId,
                    'retry_affiliate' => "{$crmSourceTitle} ({$crmAffId})"
                ];
            }
        } else {
            // Order was deleted or does not exist in Konnektive CRM
            $this->db->prepare("
                UPDATE retry_attempts 
                SET status = 'blocked', response_message = 'BLOCKED: Order not found in Konnektive CRM (deleted or removed).', updated_at = NOW() 
                WHERE id = :id
            ")->execute([':id' => $attemptId]);

            $this->db->prepare("
                UPDATE orders 
                SET retry_eligibility = 'ORDER_NOT_FOUND_IN_CRM', eligibility_reason = 'Order not found in Konnektive CRM (deleted or removed).', retry_status = 'paused', updated_at = NOW() 
                WHERE order_id = :oid
            ")->execute([':oid' => $orderId]);

            return [
                'success' => false,
                'status' => 'ORDER_NOT_FOUND',
                'message' => "Order #{$orderId} does not exist in Konnektive CRM. It was removed/deleted from the CRM."
            ];
        }

        // Exclude test orders from live execution
        if (!empty($eval['is_test_order'])) {
            $this->db->prepare("
                UPDATE retry_attempts 
                SET status = 'blocked', response_message = 'BLOCKED: Test orders are excluded from live execution.', updated_at = NOW() 
                WHERE id = :id
            ")->execute([':id' => $attemptId]);

            $this->db->prepare("
                UPDATE orders 
                SET retry_eligibility = 'TEST_ORDER_EXCLUDED', eligibility_reason = 'Test order excluded from live retry execution.', retry_status = 'paused', updated_at = NOW() 
                WHERE order_id = :oid
            ")->execute([':oid' => $orderId]);

            return [
                'success' => false,
                'message' => 'Attempt blocked: Test orders are strictly excluded from live payment execution.',
                'status' => 'TEST_ORDER_EXCLUDED'
            ];
        }

        // -----------------------------------------------------------------
        // STEP 2: VERIFY AND DECRYPT CAPTURED CARD CREDENTIALS
        // Required for creating a new order at custom/manual price via /order/import/
        // -----------------------------------------------------------------
        // If card was not captured at checkout or normal rerun mode is requested, execute Normal CRM Rerun via /order/rerun/ at original price
        if (empty($order['cvv_data']) || !empty($isNormalRerunMode) || $preferredMode === 'normal') {
            return $this->executeNormalCrmRerun($order, $attemptId, $attemptNum, $executedBy, $isManual);
        }

        $cardJson = $this->decryptCard($order['cvv_data']);
        $cardData = !empty($cardJson) ? json_decode($cardJson, true) : null;
        if (empty($cardData) || empty($cardData['card'])) {
            // Card decryption failed - fallback to Normal Konnektive Rerun using CRM stored card
            return $this->executeNormalCrmRerun($order, $attemptId, $attemptNum, $executedBy, $isManual, true);
        }

        // -----------------------------------------------------------------
        // STEP 3: PREPARE NEW ORDER IMPORT WITH CUSTOM/MANUAL PRICING
        // Pulls customer billing & shipping details from CRM order query or local record
        // Creates a separate new order in Konnektive via /order/import/
        // Charges the exact custom/manual rerun price and links back to main order.
        // -----------------------------------------------------------------
        $fullName = trim((string)($order['customer_name'] ?? ''));
        $nameParts = explode(' ', $fullName, 2);
        $fallbackFname = !empty($nameParts[0]) ? $nameParts[0] : 'Customer';
        $fallbackLname = !empty($nameParts[1]) ? $nameParts[1] : 'Order';

        $firstName = !empty($crmOrder['shipFirstName']) ? $crmOrder['shipFirstName'] : (!empty($crmOrder['firstName']) ? $crmOrder['firstName'] : $fallbackFname);
        $lastName = !empty($crmOrder['shipLastName']) ? $crmOrder['shipLastName'] : (!empty($crmOrder['lastName']) ? $crmOrder['lastName'] : $fallbackLname);
        $address1 = !empty($crmOrder['shipAddress1']) ? $crmOrder['shipAddress1'] : (!empty($crmOrder['address1']) ? $crmOrder['address1'] : ($order['address1'] ?: '123 Main St'));
        $address2 = !empty($crmOrder['shipAddress2']) ? $crmOrder['shipAddress2'] : (!empty($crmOrder['address2']) ? $crmOrder['address2'] : '');
        $city = !empty($crmOrder['shipCity']) ? $crmOrder['shipCity'] : (!empty($crmOrder['city']) ? $crmOrder['city'] : ($order['city'] ?: 'City'));
        $state = !empty($crmOrder['shipState']) ? $crmOrder['shipState'] : (!empty($crmOrder['state']) ? $crmOrder['state'] : ($order['state'] ?: 'NY'));
        $postalCode = !empty($crmOrder['shipPostalCode']) ? $crmOrder['shipPostalCode'] : (!empty($crmOrder['postalCode']) ? $crmOrder['postalCode'] : ($order['postal_code'] ?: '10001'));
        $country = !empty($crmOrder['shipCountry']) ? $crmOrder['shipCountry'] : (!empty($crmOrder['country']) ? $crmOrder['country'] : ($order['country'] ?: 'US'));
        $email = !empty($crmOrder['emailAddress']) ? $crmOrder['emailAddress'] : ($order['customer_email'] ?: 'customer@example.com');
        $phone = !empty($crmOrder['phoneNumber']) ? $crmOrder['phoneNumber'] : ($order['customer_phone'] ?: '5555555555');
        $ipAddress = !empty($crmOrder['ipAddress']) ? $crmOrder['ipAddress'] : ($order['ip_address'] ?: '127.0.0.1');
        $campaignId = (int)($order['campaign_id'] ?: ($crmOrder['campaignId'] ?? 0));
        $productId = (int)($order['product_id'] ?: ($crmOrder['items'][0]['productId'] ?? 0));

        // -----------------------------------------------------------------
        // STEP 3A: CREATE SEPARATE RETRY LEAD ORDER IN KONNEKTIVE CRM
        // Secures an independent, dedicated CRM Order ID for this retry attempt.
        // Uses disableCustomerDedup=1 so Konnektive NEVER merges into or mutates
        // the original order or customer profile.
        // Pre-attributes this new order exclusively to the verified R_ affiliate.
        // -----------------------------------------------------------------
        $newRetryOrderId = null;
        try {
            $leadPayload = [
                'campaignId'           => $campaignId,
                'ipAddress'            => $ipAddress,
                'firstName'            => $firstName,
                'lastName'             => $lastName,
                'address1'             => $address1,
                'city'                 => $city,
                'state'                => $state,
                'postalCode'           => $postalCode,
                'country'              => $country,
                'phoneNumber'          => $phone,
                'emailAddress'         => $email,
                'shipFirstName'        => $firstName,
                'shipLastName'         => $lastName,
                'shipAddress1'         => $address1,
                'shipCity'             => $city,
                'shipState'            => $state,
                'shipPostalCode'       => $postalCode,
                'shipCountry'          => $country,
                'affId'                => $retryAffId,
                'sourceValue1'         => $submittedSv1, // 'retry_order'
                'sourceValue2'         => '',
                'sourceValue3'         => '',
                'custom1'              => 'retry_of_' . $orderId,
                'custom2'              => $orderId,
                'custom3'              => 'attempt_' . $attemptNum,
                'disableCustomerDedup' => 1,
            ];
            if (!empty($address2)) {
                $leadPayload['address2'] = $address2;
                $leadPayload['shipAddress2'] = $address2;
            }
            $leadResp = $this->api->request('leads/import/', $leadPayload);
            if (!empty($leadResp) && ($leadResp['result'] ?? '') === 'SUCCESS' && !empty($leadResp['message']['orderId'])) {
                $newRetryOrderId = (string)$leadResp['message']['orderId'];
            }
        } catch (Throwable $e) {
            // If lead pre-registration fails, proceed to order/import with disableCustomerDedup
        }

        // -----------------------------------------------------------------
        // STEP 3B: EXECUTE NEW ORDER IMPORT AT CUSTOM/MANUAL PRICING
        // Targets the newly secured independent order ID ($newRetryOrderId).
        // Charges the exact custom/manual rerun price and links back to parent.
        // -----------------------------------------------------------------
        $importPayload = [
            'campaignId'        => $campaignId,
            'ipAddress'         => $ipAddress,
            'firstName'         => $firstName,
            'lastName'          => $lastName,
            'address1'          => $address1,
            'city'              => $city,
            'state'             => $state,
            'postalCode'        => $postalCode,
            'country'           => $country,
            'billShipSame'      => 1,
            'phoneNumber'       => $phone,
            'emailAddress'      => $email,
            'shipFirstName'     => $firstName,
            'shipLastName'      => $lastName,
            'shipAddress1'      => $address1,
            'shipCity'          => $city,
            'shipState'         => $state,
            'shipPostalCode'    => $postalCode,
            'shipCountry'       => $country,

            // Captured card details decrypted from database:
            'paySource'         => 'CREDITCARD',
            'cardNumber'        => $cardData['card'],
            'cardMonth'         => $cardData['month'],
            'cardYear'          => $cardData['year'],
            'cardSecurityCode'  => $cardData['cvv'] ?? '',

            // Target custom/manual price:
            'product1_id'       => $productId,
            'product1_qty'      => 1,
            'product1_price'    => number_format($targetPrice, 2, '.', ''),

            // Attribution & Pixel Suppression:
            'affId'             => $retryAffId,
            'sourceValue1'      => $submittedSv1, // 'retry_order'
            'sourceValue2'      => '',
            'sourceValue3'      => '',

            // Konnektive custom fields linking back to parent order:
            'custom1'           => 'retry_of_' . $orderId,
            'custom2'           => $orderId,
            'custom3'           => 'attempt_' . $attemptNum,
            'disableCustomerDedup' => 1,
        ];

        if (!empty($newRetryOrderId)) {
            $importPayload['orderId'] = $newRetryOrderId;
        }

        if (!empty($address2)) {
            $importPayload['address2'] = $address2;
            $importPayload['shipAddress2'] = $address2;
        }
        if (!empty($order['merchant_id'])) {
            $importPayload['forceMerchantId'] = $order['merchant_id'];
        }

        // Execute /order/import/
        $apiResp = $this->api->importOrder($importPayload);

        // Network timeout / connection failure handling
        if (!empty($apiResp['is_timeout'])) {
            $this->db->prepare("
                UPDATE retry_attempts 
                SET status = 'needs_reconciliation', reconciliation_status = 'pending_verification', response_message = :msg, updated_at = NOW() 
                WHERE id = :id
            ")->execute([':msg' => $apiResp['message'], ':id' => $attemptId]);

            $this->db->prepare("
                UPDATE orders 
                SET retry_status = 'needs_review', eligibility_reason = 'Network timeout during charge request. Status reconciliation pending.', updated_at = NOW() 
                WHERE order_id = :oid
            ")->execute([':oid' => $orderId]);

            AuditLogger::log('retry_timeout', 'order', $orderId, ['attempt_id' => $attemptId, 'error' => $apiResp['message']], $executedBy);
            return [
                'success' => false,
                'is_timeout' => true,
                'message' => 'API timeout occurred. Order flagged for status reconciliation before any further action.'
            ];
        }

        $resultStatus = $apiResp['result'] ?? 'ERROR';
        $message = is_string($apiResp['message'] ?? null) ? $apiResp['message'] : json_encode($apiResp['message'] ?? []);
        
        $newOrderId = $newRetryOrderId;
        $transId = null;
        if (!empty($apiResp['message']['orderId'])) {
            $newOrderId = (string)$apiResp['message']['orderId'];
        }
        if (!empty($apiResp['message']['transactionId'])) {
            $transId = (string)$apiResp['message']['transactionId'];
        }

        if ($resultStatus === 'SUCCESS' && !empty($newOrderId)) {
            // STOP CONDITION: Success achieved! New retry order created & linked to parent.
            $this->db->prepare("
                UPDATE retry_attempts 
                SET status = 'successful', charged_price = :prc, pricing_rule_source = :src, 
                    konnektive_order_id = :koid, konnektive_trans_id = :tid, 
                    response_message = :msg, executed_at = NOW(), updated_at = NOW() 
                WHERE id = :id
            ")->execute([
                ':prc' => $targetPrice,
                ':src' => $ruleSource,
                ':koid' => $newOrderId,
                ':tid' => $transId,
                ':msg' => "New retry order #{$newOrderId} created and approved at \${$targetPrice} (linked to original #{$orderId}).",
                ':id' => $attemptId
            ]);

            $this->db->prepare("
                UPDATE orders 
                SET retry_status = 'successful',
                    retry_order_id = :roid,
                    transaction_status = 'COMPLETE',
                    retry_eligibility = 'COMPLETED',
                    eligibility_reason = CONCAT('Order recovered: Created new order #', :roid2, ' at \$', :prc, ' via ', :src, ' [R_ Affiliate: ', COALESCE(:raname, 'None'), ']. Linked to parent order #', :oid, '.'),
                    retry_aff_id = :raid,
                    retry_aff_name = :raname2,
                    planned_price = :prc2,
                    pricing_rule_source = :src2,
                    attempt_count = :att,
                    last_attempt_at = NOW(),
                    next_attempt_at = NULL,
                    updated_at = NOW() 
                WHERE order_id = :oid
            ")->execute([
                ':roid' => $newOrderId,
                ':roid2' => $newOrderId,
                ':prc' => $targetPrice,
                ':prc2' => $targetPrice,
                ':src' => $ruleSource,
                ':src2' => $ruleSource,
                ':raid' => $retryAffId,
                ':raname' => $retryAffName,
                ':raname2' => $retryAffName,
                ':att' => $attemptNum,
                ':oid' => $orderId
            ]);

            AuditLogger::log('retry_order_created', 'order', $orderId, [
                'parent_order_id' => $orderId,
                'new_order_id' => $newOrderId,
                'attempt_id' => $attemptId,
                'attempt_number' => $attemptNum,
                'charged_price' => $targetPrice,
                'rule_source' => $ruleSource,
                'transaction_id' => $transId,
                'original_affiliate' => "{$origAffName} ({$origAffId})",
                'retry_affiliate' => "{$retryAffName} ({$retryAffId})",
                'executed_by' => $executedBy ?: 'system'
            ], $executedBy);

            return [
                'success' => true,
                'status' => 'SUCCESS',
                'order_id' => $newOrderId,
                'parent_order_id' => $orderId,
                'charged_price' => $targetPrice,
                'message' => "Payment approved! Successfully created new retry order #{$newOrderId} at \${$targetPrice}. Linked to parent order #{$orderId}.",
                'retry_affiliate' => "{$retryAffName} ({$retryAffId})"
            ];
        } else {
            // User requirement: "we are only gonna use normal rerun if all of the other options fails"
            // If custom-price /order/import/ failed, attempt fallback to Normal Konnektive Rerun (/order/rerun/) at original price
            if (!empty($crmOrder) && !empty($orderId)) {
                $normalFallback = $this->executeNormalCrmRerun($order, $attemptId, $attemptNum, $executedBy, $isManual, true);
                if (!empty($normalFallback['success'])) {
                    return $normalFallback;
                }
            }

            // Transaction declined or rejected by gateway/CRM
            $this->db->prepare("
                UPDATE retry_attempts 
                SET status = 'declined', charged_price = :prc, pricing_rule_source = :src,
                    konnektive_order_id = :koid, konnektive_trans_id = :tid, 
                    response_message = :msg, executed_at = NOW(), updated_at = NOW() 
                WHERE id = :id
            ")->execute([
                ':prc' => $targetPrice,
                ':src' => $ruleSource,
                ':koid' => $newOrderId ?: $orderId,
                ':tid' => $transId,
                ':msg' => $message,
                ':id' => $attemptId
            ]);

            $isRetryable = $this->isDeclineRetryable($message);
            $maxAttempts = (int)($eval['campaign']['max_attempts'] ?? 3);

            if (!$isRetryable || $attemptNum >= $maxAttempts) {
                // Terminal decline: cease retries
                $stopReason = !$isRetryable ? 'Terminal decline reason received.' : 'Max retry attempts reached.';
                $this->db->prepare("
                    UPDATE orders 
                    SET retry_status = 'declined',
                        retry_eligibility = 'COMPLETED_DECLINED',
                        retry_order_id = COALESCE(:roid, retry_order_id),
                        retry_aff_id = COALESCE(:raid, retry_aff_id),
                        retry_aff_name = COALESCE(:raname, retry_aff_name),
                        eligibility_reason = :rs,
                        attempt_count = :att,
                        last_attempt_at = NOW(),
                        next_attempt_at = NULL,
                        updated_at = NOW() 
                    WHERE order_id = :oid
                ")->execute([
                    ':roid' => $newOrderId,
                    ':raid' => $retryAffId,
                    ':raname' => $retryAffName,
                    ':rs' => $stopReason,
                    ':att' => $attemptNum,
                    ':oid' => $orderId
                ]);
            } else {
                // Schedule next attempt with delay
                $delay = (int)($eval['campaign']['retry_delay_minutes'] ?? 60);
                $nextDate = date('Y-m-d H:i:s', strtotime("+{$delay} minutes"));
                $this->db->prepare("
                    UPDATE orders 
                    SET retry_status = 'pending',
                        retry_order_id = COALESCE(:roid, retry_order_id),
                        retry_aff_id = COALESCE(:raid, retry_aff_id),
                        retry_aff_name = COALESCE(:raname, retry_aff_name),
                        attempt_count = :att,
                        last_attempt_at = NOW(),
                        next_attempt_at = :nxt,
                        eligibility_reason = CONCAT('Declined: ', :msg),
                        updated_at = NOW() 
                    WHERE order_id = :oid
                ")->execute([
                    ':roid' => $newOrderId,
                    ':raid' => $retryAffId,
                    ':raname' => $retryAffName,
                    ':att' => $attemptNum,
                    ':nxt' => $nextDate,
                    ':msg' => substr($message, 0, 100),
                    ':oid' => $orderId
                ]);
            }

            AuditLogger::log('retry_declined', 'order', $orderId, [
                'attempt_id' => $attemptId,
                'attempt_number' => $attemptNum,
                'decline_message' => $message,
                'order_id' => $orderId,
                'original_affiliate' => "{$origAffName} ({$origAffId})",
                'retry_affiliate' => "{$retryAffName} ({$retryAffId})"
            ], $executedBy);

            return [
                'success' => false,
                'status' => 'DECLINED',
                'message' => 'Transaction declined by issuer: ' . $message,
                'attempt_number' => $attemptNum,
                'parent_order_id' => $orderId
            ];
        }
    }

    /**
     * Executes Normal Konnektive Rerun (/order/rerun/) at Original Order Price
     * Uses Konnektive's vaulted token on file. Zero raw cardholder data handled.
     * Serves as reliable fallback when custom-price new order import fails or card was not captured at checkout.
     */
    public function executeNormalCrmRerun(array $order, int $attemptId, int $attemptNum, ?string $executedBy = null, bool $isManual = false, bool $isFallback = false): array {
        $orderId = $order['order_id'];
        $crmOrderId = !empty($order['actual_order_id']) ? $order['actual_order_id'] : $orderId;
        $originalPrice = (float)($order['original_amount'] ?: ($order['initial_amount'] ?: 0.0));
        if ($originalPrice <= 0.0) {
            $originalPrice = (float)($order['planned_price'] ?: 0.0);
        }

        // Check global dry-run mode
        $settingsStmt = $this->db->query("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('global_live_retries_enabled', 'dry_run_mode')");
        $settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $globalLive = (int)($settings['global_live_retries_enabled'] ?? 0) === 1;
        $dryRun = (int)($settings['dry_run_mode'] ?? 1) === 1 || !$globalLive || $executedBy === 'automated_test';

        $ruleSource = $isFallback ? 'NORMAL_CRM_RERUN_FALLBACK' : 'NORMAL_CRM_RERUN';
        $mapping = $this->affiliateManager->getMappingForAffiliate($order['aff_id'] ?? ($order['source_title'] ?? ''));
        $retryAffId = $mapping['retry_aff_id'] ?? null;
        $retryAffName = $mapping['retry_title'] ?? null;

        if ($dryRun) {
            $simulatedMsg = "DRY-RUN SIMULATION: Normal Konnektive Rerun evaluated for Order #{$orderId} via /order/rerun/ at original price \${$originalPrice}" . ($isFallback ? " (Fallback after custom import)" : "") . ". Live payments disabled.";
            $this->db->prepare("
                UPDATE retry_attempts 
                SET status = 'dry_run_completed', konnektive_order_id = :roid, planned_price = :prc, 
                    pricing_rule_source = :src, response_message = :msg, updated_at = NOW() 
                WHERE id = :id
            ")->execute([':roid' => $crmOrderId, ':prc' => $originalPrice, ':src' => $ruleSource, ':msg' => $simulatedMsg, ':id' => $attemptId]);

            $this->db->prepare("
                UPDATE orders 
                SET retry_status = 'pending', attempt_count = :att, last_attempt_at = NOW(), 
                    planned_price = :prc, pricing_rule_source = :src, updated_at = NOW() 
                WHERE order_id = :oid
            ")->execute([
                ':att' => $attemptNum,
                ':prc' => $originalPrice,
                ':src' => $ruleSource,
                ':oid' => $orderId
            ]);

            AuditLogger::log('retry_normal_dry_run', 'order', $orderId, [
                'attempt_id' => $attemptId,
                'mode' => $ruleSource,
                'original_price' => $originalPrice,
                'executed_by' => $executedBy ?: 'system'
            ], $executedBy);

            return [
                'success' => true,
                'is_dry_run' => true,
                'mode' => $ruleSource,
                'order_id' => $orderId,
                'charged_price' => $originalPrice,
                'message' => $simulatedMsg
            ];
        }

        // Live execution via /order/rerun/
        $billerId = !empty($order['merchant_id']) ? (string)$order['merchant_id'] : null;
        $rerunResp = $this->api->rerunOrder($crmOrderId, $billerId, $retryAffId);
        $result = $rerunResp['result'] ?? 'ERROR';
        $message = is_string($rerunResp['message'] ?? null) ? $rerunResp['message'] : json_encode($rerunResp['message'] ?? []);

        if ($result === 'SUCCESS') {
            $this->db->prepare("
                UPDATE retry_attempts 
                SET status = 'successful', charged_price = :prc, pricing_rule_source = :src, 
                    konnektive_order_id = :koid, response_message = :msg, executed_at = NOW(), updated_at = NOW() 
                WHERE id = :id
            ")->execute([
                ':prc' => $originalPrice,
                ':src' => $ruleSource,
                ':koid' => $crmOrderId,
                ':msg' => "Order #{$crmOrderId} approved via Normal Konnektive Rerun at original price \${$originalPrice}.",
                ':id' => $attemptId
            ]);

            $this->db->prepare("
                UPDATE orders 
                SET retry_status = 'successful',
                    transaction_status = 'COMPLETE',
                    retry_eligibility = 'COMPLETED',
                    eligibility_reason = CONCAT('Order recovered via Normal Konnektive Rerun at original price \$', :prc),
                    planned_price = :prc2,
                    pricing_rule_source = :src2,
                    attempt_count = :att,
                    last_attempt_at = NOW(),
                    next_attempt_at = NULL,
                    crm_status = 'SYNCED',
                    crm_verified_at = NOW(),
                    updated_at = NOW() 
                WHERE order_id = :oid
            ")->execute([
                ':prc' => $originalPrice,
                ':prc2' => $originalPrice,
                ':src2' => $ruleSource,
                ':att' => $attemptNum,
                ':oid' => $orderId
            ]);

            AuditLogger::log('retry_normal_rerun_success', 'order', $orderId, [
                'attempt_id' => $attemptId,
                'mode' => $ruleSource,
                'original_price' => $originalPrice,
                'executed_by' => $executedBy ?: 'system'
            ], $executedBy);

            return [
                'success' => true,
                'status' => 'SUCCESS',
                'mode' => $ruleSource,
                'message' => "Order #{$orderId} was APPROVED via Normal Konnektive Rerun at original price \${$originalPrice}!",
                'charged_price' => $originalPrice,
                'konnektive_order_id' => $crmOrderId
            ];
        } else {
            // Decline handling for normal rerun
            $this->db->prepare("
                UPDATE retry_attempts 
                SET status = 'declined', charged_price = :prc, pricing_rule_source = :src, 
                    konnektive_order_id = :koid, response_message = :msg, executed_at = NOW(), updated_at = NOW() 
                WHERE id = :id
            ")->execute([
                ':prc' => $originalPrice,
                ':src' => $ruleSource,
                ':koid' => $crmOrderId,
                ':msg' => $message,
                ':id' => $attemptId
            ]);

            $isRetryable = $this->isDeclineRetryable($message);
            $maxAttempts = 3;
            if (!$isRetryable || $attemptNum >= $maxAttempts) {
                $this->db->prepare("
                    UPDATE orders 
                    SET retry_status = 'declined', retry_eligibility = 'COMPLETED_DECLINED',
                        eligibility_reason = :rs, attempt_count = :att, last_attempt_at = NOW(), next_attempt_at = NULL, updated_at = NOW() 
                    WHERE order_id = :oid
                ")->execute([
                    ':rs' => !$isRetryable ? 'Terminal decline reason received.' : 'Max retry attempts reached.',
                    ':att' => $attemptNum,
                    ':oid' => $orderId
                ]);
            } else {
                $nextDate = date('Y-m-d H:i:s', strtotime("+60 minutes"));
                $this->db->prepare("
                    UPDATE orders 
                    SET retry_status = 'pending', attempt_count = :att, last_attempt_at = NOW(), next_attempt_at = :nxt,
                        eligibility_reason = CONCAT('Declined: ', :msg), updated_at = NOW() 
                    WHERE order_id = :oid
                ")->execute([
                    ':att' => $attemptNum,
                    ':nxt' => $nextDate,
                    ':msg' => substr($message, 0, 100),
                    ':oid' => $orderId
                ]);
            }

            AuditLogger::log('retry_normal_declined', 'order', $orderId, [
                'attempt_id' => $attemptId,
                'mode' => $ruleSource,
                'error' => $message,
                'executed_by' => $executedBy ?: 'system'
            ], $executedBy);

            return [
                'success' => false,
                'status' => 'DECLINED',
                'mode' => $ruleSource,
                'message' => 'Normal Konnektive Rerun declined: ' . $message,
                'attempt_number' => $attemptNum
            ];
        }
    }

    /**
     * Ingests a single declined order directly from Konnektive CRM by Order ID
     */
    public function importSingleDeclinedOrderFromCRM(string $orderId, ?string $cvvData = null, ?string $cardLast4 = null, ?string $cardExp = null, ?string $cardType = null): ?int {
        try {
            $resp = $this->api->request('order/query/', ['orderId' => $orderId]);
            $orders = $resp['message']['data'] ?? [];
            if (empty($orders[0])) return null;
            $order = $orders[0];

            $oid = trim((string)($order['orderId'] ?? ''));
            if ($oid === '') return null;

            $actualOrderId = !empty($order['actualOrderId']) ? (string)$order['actualOrderId'] : null;
            $campId = (int)($order['campaignId'] ?? 0);
            $campaignName = !empty($order['campaignName']) ? trim($order['campaignName']) : null;
            $amount = (float)($order['totalAmount'] ?? 0.0);
            $shippingAmount = (float)($order['baseShipping'] ?? 0.0);
            $salesTax = (float)($order['salesTax'] ?? 0.0);
            $currency = !empty($order['currencyCode']) ? $order['currencyCode'] : (!empty($order['currency']) ? $order['currency'] : 'USD');
            
            $items = $order['items'] ?? [];
            $firstItem = !empty($items) ? reset($items) : [];
            $prodId = (int)($firstItem['productId'] ?? 0);

            $rawResponseText = trim((string)($firstItem['responseText'] ?? ''));
            if ($rawResponseText !== '' && strtoupper($rawResponseText) !== 'DECLINED') {
                $declineReason = $rawResponseText;
            } elseif (!empty($firstItem['responseType'])) {
                $rt = strtoupper(trim((string)$firstItem['responseType']));
                $declineReason = ($rt === 'SOFT_DECLINE') ? 'Soft Decline' : (($rt === 'HARD_DECLINE') ? 'Hard Decline' : trim((string)$firstItem['responseType']));
            } else {
                $declineReason = 'Declined';
            }

            $customerEmail = trim($order['emailAddress'] ?? '');
            $customerName = trim(($order['firstName'] ?? '') . ' ' . ($order['lastName'] ?? ''));
            $customerPhone = trim($order['phoneNumber'] ?? '');
            $customerId = !empty($order['customerId']) ? (string)$order['customerId'] : null;
            $paySourceId = !empty($order['paySourceId']) ? (string)$order['paySourceId'] : null;

            $paySource = !empty($order['paySource']) ? trim($order['paySource']) : 'CREDITCARD';
            $resolvedCardType = !empty($cardType) ? $cardType : (!empty($order['cardType']) ? trim($order['cardType']) : null);
            $resolvedCardLast4 = !empty($cardLast4) ? $cardLast4 : (!empty($order['cardLast4']) ? trim($order['cardLast4']) : null);
            $resolvedCardExp = !empty($cardExp) ? $cardExp : (!empty($order['cardExpiryDate']) ? trim($order['cardExpiryDate']) : null);

            $affId = !empty($order['affId']) ? mb_substr(trim($order['affId']), 0, 120) : null;
            $sourceTitle = !empty($order['sourceTitle']) ? mb_substr(trim($order['sourceTitle']), 0, 120) : null;
            $sourceValue1 = !empty($order['sourceValue1']) ? mb_substr(trim($order['sourceValue1']), 0, 250) : null;
            $sourceValue2 = !empty($order['sourceValue2']) ? mb_substr(trim($order['sourceValue2']), 0, 250) : null;
            $sourceValue3 = !empty($order['sourceValue3']) ? mb_substr(trim($order['sourceValue3']), 0, 250) : null;

            $address1 = !empty($order['address1']) ? mb_substr(trim($order['address1']), 0, 250) : null;
            $city = !empty($order['city']) ? mb_substr(trim($order['city']), 0, 100) : null;
            $state = !empty($order['state']) ? mb_substr(trim($order['state']), 0, 50) : null;
            $country = !empty($order['country']) ? mb_substr(trim($order['country']), 0, 10) : null;
            $postalCode = !empty($order['postalCode']) ? mb_substr(trim($order['postalCode']), 0, 20) : null;
            $ipAddress = !empty($order['ipAddress']) ? mb_substr(trim($order['ipAddress']), 0, 60) : null;

            $merchantId = !empty($firstItem['merchantId']) ? (string)$firstItem['merchantId'] : null;
            $merchantName = !empty($firstItem['merchant']) ? mb_substr(trim($firstItem['merchant']), 0, 250) : null;

            $testOrderStatus = 'live';
            $orderTypeUpper = strtoupper(trim($order['orderType'] ?? ''));
            $merchantLower = strtolower($merchantName ?? '');
            if ($orderTypeUpper === 'TEST' || strpos($merchantLower, 'test') !== false || strpos($merchantLower, 'sandbox') !== false) {
                $testOrderStatus = 'test';
            }

            $allProdIds = [];
            foreach ($items as $item) {
                if (!empty($item['productId'])) $allProdIds[] = (int)$item['productId'];
            }
            $prodIdsJson = !empty($allProdIds) ? json_encode($allProdIds) : null;

            $associatedOfferId = null;
            $associatedOfferName = null;
            $offerCheck = $this->db->prepare("
                SELECT o.offer_id, o.offer_name 
                FROM offer_campaign_products ocp
                LEFT JOIN campaign_products cp ON cp.campaign_id = ocp.campaign_id AND cp.product_id = ocp.product_id
                JOIN offers o ON ocp.offer_id = o.offer_id
                WHERE ocp.campaign_id = :cid 
                  AND (ocp.product_id = :pid OR cp.campaign_product_id = :pid) 
                  AND o.is_active = 1
            ");
            $offerCheck->execute([':cid' => $campId, ':pid' => $prodId]);
            $matchingOffers = $offerCheck->fetchAll(PDO::FETCH_ASSOC);

            if (count($matchingOffers) === 1) {
                $associatedOfferId = $matchingOffers[0]['offer_id'];
                $associatedOfferName = $matchingOffers[0]['offer_name'];
            } else {
                $defOffStmt = $this->db->prepare("SELECT offer_id, offer_name FROM offers WHERE default_campaign_id = :cid AND is_active = 1 LIMIT 1");
                $defOffStmt->execute([':cid' => $campId]);
                $defOff = $defOffStmt->fetch(PDO::FETCH_ASSOC);
                if ($defOff) {
                    $associatedOfferId = $defOff['offer_id'];
                    $associatedOfferName = $defOff['offer_name'];
                }
            }

            $mapping = ($affId || $sourceTitle) ? $this->affiliateManager->getMappingForAffiliate($affId, $sourceTitle) : null;
            $affMappingStatus = $mapping ? $mapping['status'] : 'unmapped';
            $retryAffId = $mapping ? $mapping['retry_aff_id'] : null;
            $retryAffName = $mapping ? $mapping['retry_title'] : null;

            $evalOrder = [
                'order_id' => $oid,
                'campaign_id' => $campId,
                'product_id' => $prodId,
                'product_ids_json' => $prodIdsJson,
                'offer_id' => $associatedOfferId,
                'aff_id' => $affId,
                'source_title' => $sourceTitle,
                'is_retry_created_order' => 0,
                'has_payment_reference' => 1,
                'custom_price_eligible' => 1,
                'cvv_data' => $cvvData,
                'test_order_status' => $testOrderStatus,
                'decline_reason' => $declineReason,
                'planned_price' => null
            ];
            $eval = $this->evaluateOrderComprehensive($evalOrder);
            $priceInfo = $this->resolvePriceDetailed($evalOrder);
            $plannedPrice = $priceInfo['price'] > 0 ? $priceInfo['price'] : $amount;
            $pricingRuleSource = $priceInfo['rule_source'];

            $chk = $this->db->prepare("SELECT id FROM orders WHERE order_id = :oid LIMIT 1");
            $chk->execute([':oid' => $oid]);
            $existingId = $chk->fetchColumn();

            if ($existingId) {
                $this->db->prepare("
                    UPDATE orders SET
                        initial_product_id = COALESCE(initial_product_id, NULLIF(product_id, 0), :init_pid),
                        initial_amount = COALESCE(initial_amount, NULLIF(original_amount, 0), :init_amt),
                        product_id = :prod_id,
                        product_ids_json = :pids_json,
                        original_amount = :amt,
                        cvv_data = COALESCE(:cvv, cvv_data),
                        card_last4 = COALESCE(:last4, card_last4),
                        card_expiry_date = COALESCE(:exp, card_expiry_date),
                        card_type = COALESCE(:ctype, card_type),
                        has_payment_reference = 1,
                        planned_price = :prc,
                        pricing_rule_source = :src,
                        updated_at = NOW()
                    WHERE id = :id
                ")->execute([
                    ':init_pid' => $prodId,
                    ':init_amt' => $amount,
                    ':prod_id' => $prodId,
                    ':pids_json' => $prodIdsJson,
                    ':amt' => $amount,
                    ':cvv' => $cvvData,
                    ':last4' => $resolvedCardLast4,
                    ':exp' => $resolvedCardExp,
                    ':ctype' => $resolvedCardType,
                    ':prc' => $plannedPrice,
                    ':src' => $pricingRuleSource,
                    ':id' => $existingId
                ]);
                $this->reEvaluateOrderEligibility((int)$existingId);
                $this->syncOrderTransactionsFromCRM($oid);
                return (int)$existingId;
            }

            $delay = (int)($eval['campaign']['retry_delay_minutes'] ?? 60);
            $nextAttempt = $eval['eligible'] ? date('Y-m-d H:i:s', strtotime("+{$delay} minutes")) : null;
            $rStatus = $eval['eligible'] ? 'scheduled' : 'paused';

            $ins = $this->db->prepare("
                INSERT INTO orders (
                    order_id, actual_order_id, customer_id, pay_source_id, campaign_id, campaign_name,
                    product_id, product_ids_json, initial_product_id, initial_amount, transaction_count,
                    offer_id, offer_name, customer_name, customer_email,
                    customer_phone, address1, city, state, country, postal_code, original_amount,
                    shipping_amount, sales_tax, currency, transaction_status, decline_reason,
                    pay_source, card_type, card_last4, card_expiry_date, cvv_data, has_payment_reference,
                    custom_price_eligible, test_order_status, merchant_id, merchant_name,
                    retry_eligibility, eligibility_reason, retry_status, next_attempt_at, planned_price,
                    pricing_rule_source, aff_id, source_title, source_value1, source_value2, source_value3,
                    is_retry_created_order, affiliate_mapping_status, retry_aff_id, retry_aff_name,
                    ip_address, created_at, updated_at
                ) VALUES (
                    :oid, :aoid, :cid, :psid, :camp_id, :camp_name,
                    :prod_id, :pids_json, :init_pid, :init_amt, 1,
                    :offer_id, :offer_name, :cname, :cemail,
                    :cphone, :addr1, :city, :state, :country, :zip, :amt,
                    :ship, :tax, :curr, 'DECLINED', :reason,
                    :psource, :ctype, :clast4, :cexp, :cvv, 1,
                    1, :tstatus, :mid, :mname,
                    :el_status, :el_reason, :r_status, :next_att, :planned_prc,
                    :rule_src, :aff, :stitle, :sv1, :sv2, :sv3,
                    0, :ams, :raid, :raname,
                    :ip, NOW(), NOW()
                )
            ");
            $ins->execute([
                ':oid' => $oid,
                ':aoid' => $actualOrderId,
                ':cid' => $customerId,
                ':psid' => $paySourceId,
                ':camp_id' => $campId,
                ':camp_name' => $campaignName,
                ':prod_id' => $prodId,
                ':pids_json' => $prodIdsJson,
                ':init_pid' => $prodId,
                ':init_amt' => $amount,
                ':offer_id' => $associatedOfferId,
                ':offer_name' => $associatedOfferName,
                ':cname' => $customerName,
                ':cemail' => $customerEmail,
                ':cphone' => $customerPhone,
                ':addr1' => $address1,
                ':city' => $city,
                ':state' => $state,
                ':country' => $country,
                ':zip' => $postalCode,
                ':amt' => $amount,
                ':ship' => $shippingAmount,
                ':tax' => $salesTax,
                ':curr' => $currency,
                ':reason' => $declineReason,
                ':psource' => $paySource,
                ':ctype' => $resolvedCardType,
                ':clast4' => $resolvedCardLast4,
                ':cexp' => $resolvedCardExp,
                ':cvv' => $cvvData,
                ':tstatus' => $testOrderStatus,
                ':mid' => $merchantId,
                ':mname' => $merchantName,
                ':el_status' => $eval['status'],
                ':el_reason' => $eval['reason'],
                ':r_status' => $rStatus,
                ':next_att' => $nextAttempt,
                ':planned_prc' => $plannedPrice,
                ':rule_src' => $pricingRuleSource,
                ':aff' => $affId,
                ':stitle' => $sourceTitle,
                ':sv1' => $sourceValue1,
                ':sv2' => $sourceValue2,
                ':sv3' => $sourceValue3,
                ':ams' => $affMappingStatus,
                ':raid' => $retryAffId,
                ':raname' => $retryAffName,
                ':ip' => $ipAddress
            ]);
            $newId = (int)$this->db->lastInsertId();
            $this->syncOrderTransactionsFromCRM($oid);
            return $newId;
        } catch (Throwable $e) {
            error_log("importSingleDeclinedOrderFromCRM error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Synchronizes declined orders from Konnektive CRM into the local retry database
     */
    public function syncDeclinedOrdersFromCRM(?string $startDate = null, ?string $endDate = null, ?int $filterCampaignId = null): array {
        if (empty($startDate)) {
            // Retrieve persisted synchronization cursor from app_settings
            $cursorStmt = $this->db->query("SELECT setting_value FROM app_settings WHERE setting_key = 'sync_cursor_datetime' LIMIT 1");
            $persistedCursor = $cursorStmt->fetchColumn();

            if (!empty($persistedCursor) && strtotime($persistedCursor) > 0) {
                $startDate = date('m/d/Y', strtotime($persistedCursor) - 86400);
            } else {
                $startDate = date('m/d/Y', strtotime('-7 days'));
            }
        }

        if (empty($endDate)) {
            $endDate = date('m/d/Y', strtotime('+1 day'));
        }

        $page = 1;
        $resultsPerPage = 200;
        $importedCount = 0;
        $syncedCount = 0;
        $latestTxDate = null;
        $errors = [];

        // Pre-fetch detailed gateway decline reasons from CRM transactions log for the window
        $txDeclineReasons = [];
        try {
            $txDeclineReasons = $this->api->fetchDeclineReasonsMap($startDate, $endDate, 2);
        } catch (Throwable $te) {
            // Non-fatal fallback
        }

        while (true) {
            $declinedResp = $this->api->queryDeclinedOrders($startDate, $endDate, $filterCampaignId, $page, $resultsPerPage);

            if ($declinedResp['result'] !== 'SUCCESS') {
                $errors[] = $declinedResp['message'] ?? 'API error';
                break;
            }

            $declinedOrders = $declinedResp['message']['data'] ?? [];
            if (!is_array($declinedOrders) || count($declinedOrders) === 0) {
                break;
            }

            foreach ($declinedOrders as $order) {
                $oid = trim((string)($order['orderId'] ?? ''));
                if ($oid === '') continue;

                $actualOrderId = !empty($order['actualOrderId']) ? (string)$order['actualOrderId'] : null;
                $campId = (int)($order['campaignId'] ?? 0);
                $campaignName = !empty($order['campaignName']) ? trim($order['campaignName']) : null;
                $amount = (float)($order['totalAmount'] ?? 0.0);
                $shippingAmount = (float)($order['baseShipping'] ?? 0.0);
                $salesTax = (float)($order['salesTax'] ?? 0.0);
                $currency = !empty($order['currencyCode']) ? $order['currencyCode'] : (!empty($order['currency']) ? $order['currency'] : 'USD');
                
                $items = $order['items'] ?? [];
                $firstItem = !empty($items) ? reset($items) : [];
                $prodId = (int)($firstItem['productId'] ?? 0);

                // Resolve real gateway decline reason
                $rawResponseText = trim((string)($firstItem['responseText'] ?? ''));
                $txReason = $txDeclineReasons[$oid] ?? null;

                if ($rawResponseText !== '' && strtoupper($rawResponseText) !== 'DECLINED') {
                    $declineReason = $rawResponseText;
                } elseif (!empty($txReason)) {
                    $declineReason = $txReason;
                } elseif (!empty($firstItem['responseType'])) {
                    $rt = strtoupper(trim((string)$firstItem['responseType']));
                    $declineReason = ($rt === 'SOFT_DECLINE') ? 'Soft Decline' : (($rt === 'HARD_DECLINE') ? 'Hard Decline' : trim((string)$firstItem['responseType']));
                } else {
                    $declineReason = 'Declined';
                }

                $customerEmail = trim($order['emailAddress'] ?? '');
                $customerName = trim(($order['firstName'] ?? '') . ' ' . ($order['lastName'] ?? ''));
                $customerPhone = trim($order['phoneNumber'] ?? '');
                $customerId = !empty($order['customerId']) ? (string)$order['customerId'] : null;
                $paySourceId = !empty($order['paySourceId']) ? (string)$order['paySourceId'] : null;

                $paySource = !empty($order['paySource']) ? trim($order['paySource']) : 'CREDITCARD';
                $cardType = !empty($order['cardType']) ? trim($order['cardType']) : null;
                $cardLast4 = !empty($order['cardLast4']) ? trim($order['cardLast4']) : null;
                $cardExpiryDate = !empty($order['cardExpiryDate']) ? trim($order['cardExpiryDate']) : null;

                $affId = !empty($order['affId']) ? mb_substr(trim($order['affId']), 0, 120) : null;
                $sourceTitle = !empty($order['sourceTitle']) ? mb_substr(trim($order['sourceTitle']), 0, 120) : null;
                $sourceValue1 = !empty($order['sourceValue1']) ? mb_substr(trim($order['sourceValue1']), 0, 250) : null;
                $sourceValue2 = !empty($order['sourceValue2']) ? mb_substr(trim($order['sourceValue2']), 0, 250) : null;
                $sourceValue3 = !empty($order['sourceValue3']) ? mb_substr(trim($order['sourceValue3']), 0, 250) : null;

                $address1 = !empty($order['address1']) ? mb_substr(trim($order['address1']), 0, 250) : null;
                $city = !empty($order['city']) ? mb_substr(trim($order['city']), 0, 100) : null;
                $state = !empty($order['state']) ? mb_substr(trim($order['state']), 0, 50) : null;
                $country = !empty($order['country']) ? mb_substr(trim($order['country']), 0, 10) : null;
                $postalCode = !empty($order['postalCode']) ? mb_substr(trim($order['postalCode']), 0, 20) : null;
                $ipAddress = !empty($order['ipAddress']) ? mb_substr(trim($order['ipAddress']), 0, 60) : null;

                $merchantId = !empty($firstItem['merchantId']) ? (string)$firstItem['merchantId'] : null;
                $merchantName = !empty($firstItem['merchant']) ? mb_substr(trim($firstItem['merchant']), 0, 250) : null;

                // Documented Test Order Detection:
                // Reliable indicators from CRM transaction/merchant metadata (not guessing from amount/name)
                $testOrderStatus = 'live';
                $orderTypeUpper = strtoupper(trim($order['orderType'] ?? ''));
                $merchantLower = strtolower($merchantName ?? '');
                if ($orderTypeUpper === 'TEST' || strpos($merchantLower, 'test') !== false || strpos($merchantLower, 'sandbox') !== false || strpos($merchantLower, 'simulator') !== false) {
                    $testOrderStatus = 'test';
                } elseif (empty($merchantId) && empty($order['orderType'])) {
                    $testOrderStatus = 'unknown';
                }

                $txDate = $order['dateCreated'] ?? ($order['dateUpdated'] ?? null);
                if ($txDate && (!$latestTxDate || strtotime($txDate) > strtotime($latestTxDate))) {
                    $latestTxDate = $txDate;
                }

                $allProdIds = [];
                foreach ($items as $item) {
                    if (!empty($item['productId'])) {
                        $allProdIds[] = (int)$item['productId'];
                    }
                }
                $prodIdsJson = !empty($allProdIds) ? json_encode($allProdIds) : null;

                // Associate offer
                $associatedOfferId = null;
                $associatedOfferName = null;

                $offerCheck = $this->db->prepare("
                    SELECT o.offer_id, o.offer_name 
                    FROM offer_campaign_products ocp
                    LEFT JOIN campaign_products cp ON cp.campaign_id = ocp.campaign_id AND cp.product_id = ocp.product_id
                    JOIN offers o ON ocp.offer_id = o.offer_id
                    WHERE ocp.campaign_id = :cid 
                      AND (ocp.product_id = :pid OR cp.campaign_product_id = :pid) 
                      AND o.is_active = 1
                ");
                $offerCheck->execute([':cid' => $campId, ':pid' => $prodId]);
                $matchingOffers = $offerCheck->fetchAll(PDO::FETCH_ASSOC);

                if (count($matchingOffers) === 1) {
                    $associatedOfferId = $matchingOffers[0]['offer_id'];
                    $associatedOfferName = $matchingOffers[0]['offer_name'];
                } elseif (count($matchingOffers) > 1) {
                    // 1. Check if one of the matching offers matches the local offer config ($productSubFolder)
                    require_once __DIR__ . '/RetryClient.php';
                    $subFolder = RetryClient::readProductSubFolderFromConfigFile();
                    if (!empty($subFolder)) {
                        foreach ($matchingOffers as $mo) {
                            if ($mo['offer_id'] === $subFolder || strpos($mo['offer_id'], $subFolder) !== false) {
                                $associatedOfferId = $mo['offer_id'];
                                $associatedOfferName = $mo['offer_name'];
                                break;
                            }
                        }
                    }

                    // 2. If all matching offers share the exact same custom retry price, safely associate without guessing
                    if (empty($associatedOfferId)) {
                        $prices = [];
                        foreach ($matchingOffers as $mo) {
                            $pStmt = $this->db->prepare("SELECT custom_retry_price FROM offer_campaign_products WHERE offer_id = :oid AND campaign_id = :cid AND product_id = :pid LIMIT 1");
                            $pStmt->execute([':oid' => $mo['offer_id'], ':cid' => $campId, ':pid' => $prodId]);
                            $p = $pStmt->fetchColumn();
                            $prices[] = $p !== false ? (float)$p : null;
                        }
                        if (count(array_unique($prices)) === 1) {
                            // Check if 'is-dtc-aa' is one of the matching offers
                            foreach ($matchingOffers as $mo) {
                                if ($mo['offer_id'] === 'is-dtc-aa') {
                                    $associatedOfferId = $mo['offer_id'];
                                    $associatedOfferName = $mo['offer_name'];
                                    break;
                                }
                            }
                            if (empty($associatedOfferId)) {
                                $associatedOfferId = $matchingOffers[0]['offer_id'];
                                $associatedOfferName = $matchingOffers[0]['offer_name'];
                            }
                        } else {
                            $associatedOfferId = null;
                        }
                    }
                } else {
                    $defOffStmt = $this->db->prepare("SELECT offer_id, offer_name FROM offers WHERE default_campaign_id = :cid AND is_active = 1 LIMIT 1");
                    $defOffStmt->execute([':cid' => $campId]);
                    $defOff = $defOffStmt->fetch(PDO::FETCH_ASSOC);
                    if ($defOff) {
                        $associatedOfferId = $defOff['offer_id'];
                        $associatedOfferName = $defOff['offer_name'];
                    }
                }

                // Check if this is an existing retry order (e.g. from previous scripts or retry import)
                $isRetryCreated = $this->affiliateManager->isRetryAffiliate($sourceTitle ?? '') || 
                                  $this->affiliateManager->isRetryAffiliate($affId ?? '') || 
                                  ($sourceValue1 === 'retry_order') ||
                                  (!empty($order['custom2'])) ||
                                  (!empty($order['custom1']) && strpos($order['custom1'], 'retry_of_') !== false);

                // Extract linked parent order ID if this is a rerun order
                $parentOrderId = null;
                if (!empty($order['custom2'])) {
                    $parentOrderId = trim($order['custom2']);
                } elseif (!empty($order['custom1']) && preg_match('/retry_of_([A-Za-z0-9_-]+)/', $order['custom1'], $m)) {
                    $parentOrderId = $m[1];
                }

                if ($isRetryCreated) {
                    $affMappingStatus = 'retry_affiliate';
                    $retryAffId = null;
                    $retryAffName = null;

                    // 1. Link to Parent Order and update Attempt telemetry
                    if ($parentOrderId) {
                        $pChk = $this->db->prepare("SELECT id, aff_id, source_title FROM orders WHERE order_id = :poid LIMIT 1");
                        $pChk->execute([':poid' => $parentOrderId]);
                        $parentRow = $pChk->fetch(PDO::FETCH_ASSOC);

                        if (!$parentRow) {
                            $parentDbId = $this->importSingleDeclinedOrderFromCRM($parentOrderId);
                            if ($parentDbId) {
                                $pChk->execute([':poid' => $parentOrderId]);
                                $parentRow = $pChk->fetch(PDO::FETCH_ASSOC);
                            }
                        }

                        $attemptNum = 1;
                        if (!empty($order['custom3']) && preg_match('/attempt_(\d+)/', $order['custom3'], $am)) {
                            $attemptNum = (int)$am[1];
                        }

                        if ($parentRow) {
                            $this->db->prepare("
                                UPDATE orders SET
                                    retry_order_id = :roid,
                                    attempt_count = GREATEST(COALESCE(attempt_count, 0), :att),
                                    last_attempt_at = NOW(),
                                    retry_aff_id = COALESCE(:raid, retry_aff_id),
                                    retry_aff_name = COALESCE(:raname, retry_aff_name),
                                    updated_at = NOW()
                                WHERE id = :pid
                            ")->execute([
                                ':roid' => $oid,
                                ':att' => $attemptNum,
                                ':raid' => $affId,
                                ':raname' => $sourceTitle,
                                ':pid' => $parentRow['id']
                            ]);
                        }

                        // Ensure snapshot exists in retry_attempts
                        $chkAtt = $this->db->prepare("SELECT id FROM retry_attempts WHERE order_id = :poid AND konnektive_order_id = :roid LIMIT 1");
                        $chkAtt->execute([':poid' => $parentOrderId, ':roid' => $oid]);
                        if (!$chkAtt->fetch()) {
                            $this->db->prepare("
                                INSERT INTO retry_attempts (
                                    order_id, attempt_number, scheduled_at, executed_at, planned_price, charged_price,
                                    pricing_rule_source, status, trigger_type, triggered_by, is_dry_run,
                                    konnektive_order_id, original_aff_id, original_aff_name, retry_aff_id, retry_aff_name,
                                    submitted_source_value1, response_message, created_at, updated_at
                                ) VALUES (
                                    :poid, :attnum, NOW(), NOW(), :prc, :prc2,
                                    :src, 'declined', 'manual', 'crm_sync', 0,
                                    :roid, :oaff, :oaffname, :raff, :raffname,
                                    'retry_order', :msg, NOW(), NOW()
                                )
                            ")->execute([
                                ':poid' => $parentOrderId,
                                ':attnum' => $attemptNum,
                                ':prc' => $amount,
                                ':prc2' => $amount,
                                ':src' => 'SHARED_CAMPAIGN_PRODUCT_OVERRIDE',
                                ':roid' => $oid,
                                ':oaff' => $parentRow['aff_id'] ?? null,
                                ':oaffname' => $parentRow['source_title'] ?? null,
                                ':raff' => $affId,
                                ':raffname' => $sourceTitle,
                                ':msg' => $declineReason
                            ]);
                        }
                    }

                    // 2. Insert or update the child rerun order in `orders`
                    // Note: Rerun orders NEVER hold cvv_data and are flagged with is_retry_created_order = 1
                    $chk = $this->db->prepare("SELECT id FROM orders WHERE order_id = :oid LIMIT 1");
                    $chk->execute([':oid' => $oid]);
                    $existingChildId = $chk->fetchColumn();

                    if ($existingChildId) {
                        $this->db->prepare("
                            UPDATE orders SET
                                actual_order_id = COALESCE(:aoid, actual_order_id),
                                campaign_name = COALESCE(:cname, campaign_name),
                                customer_name = COALESCE(:cname2, customer_name),
                                customer_email = COALESCE(:cemail, customer_email),
                                customer_phone = COALESCE(:cphone, customer_phone),
                                original_amount = :amt,
                                decline_reason = :dreason,
                                aff_id = :aff,
                                source_title = :stitle,
                                source_value1 = :sv1,
                                test_order_status = :tstatus,
                                is_retry_created_order = 1,
                                affiliate_mapping_status = 'retry_affiliate',
                                retry_eligibility = 'BLOCKED_RETRY_CREATED_ORDER',
                                eligibility_reason = 'Existing retry-created orders cannot enter the retry workflow as original declines.',
                                retry_status = 'paused',
                                cvv_data = NULL,
                                updated_at = NOW()
                            WHERE id = :id
                        ")->execute([
                            ':aoid' => $actualOrderId,
                            ':cname' => $campaignName,
                            ':cname2' => $customerName,
                            ':cemail' => $customerEmail,
                            ':cphone' => $customerPhone,
                            ':amt' => $amount,
                            ':dreason' => $declineReason,
                            ':aff' => $affId,
                            ':stitle' => $sourceTitle,
                            ':sv1' => $sourceValue1,
                            ':tstatus' => $testOrderStatus,
                            ':id' => $existingChildId
                        ]);
                    } else {
                        $this->db->prepare("
                            INSERT INTO orders (
                                order_id, actual_order_id, customer_id, pay_source_id, campaign_id, campaign_name,
                                product_id, product_ids_json, offer_id, offer_name, customer_name, customer_email,
                                customer_phone, address1, city, state, country, postal_code, original_amount,
                                shipping_amount, sales_tax, currency, transaction_status, decline_reason,
                                pay_source, card_type, card_last4, card_expiry_date, cvv_data, has_payment_reference,
                                custom_price_eligible, test_order_status, merchant_id, merchant_name,
                                retry_eligibility, eligibility_reason, retry_status, next_attempt_at, planned_price,
                                pricing_rule_source, aff_id, source_title, source_value1, source_value2, source_value3,
                                is_retry_created_order, affiliate_mapping_status, retry_aff_id, retry_aff_name,
                                ip_address, created_at, updated_at
                            ) VALUES (
                                :oid, :aoid, :cid, :psid, :camp_id, :camp_name,
                                :prod_id, :pids_json, :offer_id, :offer_name, :cname, :cemail,
                                :cphone, :addr1, :city, :state, :country, :zip, :amt,
                                :ship, :tax, :curr, 'DECLINED', :reason,
                                :psource, :ctype, :clast4, :cexp, NULL, 1,
                                1, :tstatus, :mid, :mname,
                                'BLOCKED_RETRY_CREATED_ORDER', 'Existing retry-created orders cannot enter the retry workflow as original declines.',
                                'paused', NULL, :planned_prc,
                                :rule_src, :aff, :stitle, :sv1, :sv2, :sv3,
                                1, 'retry_affiliate', NULL, NULL,
                                :ip, NOW(), NOW()
                            )
                        ")->execute([
                            ':oid' => $oid,
                            ':aoid' => $actualOrderId,
                            ':cid' => $customerId,
                            ':psid' => $paySourceId,
                            ':camp_id' => $campId,
                            ':camp_name' => $campaignName,
                            ':prod_id' => $prodId,
                            ':pids_json' => $prodIdsJson,
                            ':offer_id' => $associatedOfferId,
                            ':offer_name' => $associatedOfferName,
                            ':cname' => $customerName,
                            ':cemail' => $customerEmail,
                            ':cphone' => $customerPhone,
                            ':addr1' => $address1,
                            ':city' => $city,
                            ':state' => $state,
                            ':country' => $country,
                            ':zip' => $postalCode,
                            ':amt' => $amount,
                            ':ship' => $shippingAmount,
                            ':tax' => $salesTax,
                            ':curr' => $currency,
                            ':reason' => $declineReason,
                            ':psource' => $paySource,
                            ':ctype' => $cardType,
                            ':clast4' => $cardLast4,
                            ':cexp' => $cardExpiryDate,
                            ':tstatus' => $testOrderStatus,
                            ':mid' => $merchantId,
                            ':mname' => $merchantName,
                            ':planned_prc' => $amount,
                            ':rule_src' => 'SHARED_CAMPAIGN_PRODUCT_OVERRIDE',
                            ':aff' => $affId,
                            ':stitle' => $sourceTitle,
                            ':sv1' => $sourceValue1,
                            ':sv2' => $sourceValue2,
                            ':sv3' => $sourceValue3,
                            ':ip' => $ipAddress
                        ]);
                    }
                    $syncedCount++;
                    continue;
                }

                $mapping = ($affId || $sourceTitle) ? $this->affiliateManager->getMappingForAffiliate($affId, $sourceTitle) : null;
                $affMappingStatus = $mapping ? $mapping['status'] : 'unmapped';
                $retryAffId = $mapping ? $mapping['retry_aff_id'] : null;
                $retryAffName = $mapping ? $mapping['retry_title'] : null;

                $evalOrder = [
                    'order_id' => $oid,
                    'campaign_id' => $campId,
                    'product_id' => $prodId,
                    'product_ids_json' => $prodIdsJson,
                    'offer_id' => $associatedOfferId,
                    'aff_id' => $affId,
                    'source_title' => $sourceTitle,
                    'is_retry_created_order' => 0,
                    'has_payment_reference' => 1,
                    'custom_price_eligible' => 1,
                    'test_order_status' => $testOrderStatus,
                    'decline_reason' => $declineReason,
                    'planned_price' => null
                ];
                $eval = $this->evaluateOrderComprehensive($evalOrder);

                $priceInfo = $this->resolvePriceDetailed($evalOrder);
                $plannedPrice = $priceInfo['price'] > 0 ? $priceInfo['price'] : $amount;
                $pricingRuleSource = $priceInfo['rule_source'];

                // Check existing
                $chk = $this->db->prepare("SELECT id, retry_status, cvv_data FROM orders WHERE order_id = :oid LIMIT 1");
                $chk->execute([':oid' => $oid]);
                $existingOrder = $chk->fetch();

                if (!$existingOrder) {
                    // Check if there is an existing DEC_ placeholder order with captured card for this customer email
                    $decChk = $this->db->prepare("
                        SELECT id, cvv_data, card_last4, card_expiry_date, card_type, offer_id, offer_name 
                        FROM orders 
                        WHERE order_id LIKE 'DEC_%' 
                          AND customer_email = :email 
                          AND cvv_data IS NOT NULL
                        ORDER BY id DESC LIMIT 1
                    ");
                    $decChk->execute([':email' => $customerEmail]);
                    $decOrder = $decChk->fetch(PDO::FETCH_ASSOC);

                    if ($decOrder) {
                        // Reconcile and promote the DEC_ placeholder into this official CRM order!
                        $updDec = $this->db->prepare("
                            UPDATE orders SET
                                order_id = :oid,
                                actual_order_id = COALESCE(:aoid, actual_order_id),
                                customer_id = COALESCE(:cid, customer_id),
                                pay_source_id = COALESCE(:psid, pay_source_id),
                                campaign_id = :camp_id,
                                campaign_name = COALESCE(:cname, campaign_name),
                                product_id = :prod_id,
                                product_ids_json = COALESCE(:pids_json, product_ids_json),
                                offer_id = COALESCE(offer_id, :offer_id),
                                offer_name = COALESCE(offer_name, :offer_name),
                                aff_id = COALESCE(:aff, aff_id),
                                source_title = COALESCE(:stitle, source_title),
                                source_value1 = COALESCE(:sv1, source_value1),
                                source_value2 = COALESCE(:sv2, source_value2),
                                source_value3 = COALESCE(:sv3, source_value3),
                                affiliate_mapping_status = :ams,
                                retry_aff_id = :raid,
                                retry_aff_name = :raname,
                                decline_reason = :dreason,
                                test_order_status = :tstatus,
                                updated_at = NOW()
                            WHERE id = :dec_id
                        ");
                        $updDec->execute([
                            ':oid' => $oid,
                            ':aoid' => $actualOrderId,
                            ':cid' => $customerId,
                            ':psid' => $paySourceId,
                            ':camp_id' => $campId,
                            ':cname' => $campaignName,
                            ':prod_id' => $prodId,
                            ':pids_json' => $prodIdsJson,
                            ':offer_id' => $associatedOfferId,
                            ':offer_name' => $associatedOfferName,
                            ':aff' => $affId,
                            ':stitle' => $sourceTitle,
                            ':sv1' => $sourceValue1,
                            ':sv2' => $sourceValue2,
                            ':sv3' => $sourceValue3,
                            ':ams' => $affMappingStatus,
                            ':raid' => $retryAffId,
                            ':raname' => $retryAffName,
                            ':dreason' => $declineReason,
                            ':tstatus' => $testOrderStatus,
                            ':dec_id' => $decOrder['id']
                        ]);

                        $this->reEvaluateOrderEligibility((int)$decOrder['id']);
                        $syncedCount++;
                        continue;
                    }
                }

                if ($existingOrder) {
                    // If existing order lacks cvv_data, merge it from an orphan DEC_ placeholder row
                    if (empty($existingOrder['cvv_data'])) {
                        $orphanChk = $this->db->prepare("
                            SELECT id, cvv_data, card_last4, card_expiry_date, card_type, offer_id, offer_name 
                            FROM orders 
                            WHERE order_id LIKE 'DEC_%' 
                              AND customer_email = :email 
                              AND cvv_data IS NOT NULL
                            ORDER BY id DESC LIMIT 1
                        ");
                        $orphanChk->execute([':email' => $customerEmail]);
                        $orphan = $orphanChk->fetch(PDO::FETCH_ASSOC);

                        if ($orphan) {
                            $this->db->prepare("
                                UPDATE orders SET
                                    cvv_data = :cvv,
                                    card_last4 = COALESCE(:clast4, card_last4),
                                    card_expiry_date = COALESCE(:cexp, card_expiry_date),
                                    card_type = COALESCE(:ctype, card_type),
                                    has_payment_reference = 1,
                                    offer_id = COALESCE(offer_id, :off_id),
                                    offer_name = COALESCE(offer_name, :off_name),
                                    updated_at = NOW()
                                WHERE id = :existing_id
                            ")->execute([
                                ':cvv' => $orphan['cvv_data'],
                                ':clast4' => $orphan['card_last4'],
                                ':cexp' => $orphan['card_expiry_date'],
                                ':ctype' => $orphan['card_type'],
                                ':off_id' => $orphan['offer_id'],
                                ':off_name' => $orphan['offer_name'],
                                ':existing_id' => $existingOrder['id']
                            ]);

                            $this->db->prepare("DELETE FROM orders WHERE id = :orphan_id")->execute([':orphan_id' => $orphan['id']]);
                            $this->reEvaluateOrderEligibility((int)$existingOrder['id']);
                        }
                    }

                    // Enrich existing record with new metadata without overwriting active retry status
                    $upd = $this->db->prepare("
                        UPDATE orders 
                        SET actual_order_id = COALESCE(:aoid, actual_order_id),
                            campaign_name = COALESCE(:cname, campaign_name),
                            customer_phone = COALESCE(:cphone, customer_phone),
                            initial_product_id = COALESCE(initial_product_id, NULLIF(product_id, 0), :prod_id),
                            initial_amount = COALESCE(initial_amount, NULLIF(original_amount, 0), :amt),
                            product_id = :prod_id,
                            product_ids_json = :pids_json,
                            original_amount = :amt,
                            shipping_amount = :ship,
                            sales_tax = :tax,
                            pay_source = COALESCE(:psource, pay_source),
                            card_type = COALESCE(:ctype, card_type),
                            card_last4 = COALESCE(:clast4, card_last4),
                            card_expiry_date = COALESCE(:cexp, card_expiry_date),
                            aff_id = CASE
                                WHEN :is_r_aff = 1 AND is_retry_created_order = 0 AND aff_id IS NOT NULL AND aff_id != '' THEN aff_id
                                ELSE COALESCE(:aff, aff_id)
                            END,
                            source_title = CASE
                                WHEN :is_r_aff = 1 AND is_retry_created_order = 0 AND source_title IS NOT NULL AND source_title != '' THEN source_title
                                ELSE COALESCE(:stitle, source_title)
                            END,
                            source_value1 = COALESCE(:sv1, source_value1),
                            source_value2 = COALESCE(:sv2, source_value2),
                            source_value3 = COALESCE(:sv3, source_value3),
                            address1 = COALESCE(:addr1, address1),
                            city = COALESCE(:city, city),
                            state = COALESCE(:state, state),
                            country = COALESCE(:country, country),
                            postal_code = COALESCE(:zip, postal_code),
                            merchant_id = COALESCE(:mid, merchant_id),
                            merchant_name = COALESCE(:mname, merchant_name),
                            test_order_status = :tstatus,
                            is_retry_created_order = :rco,
                            affiliate_mapping_status = :ams,
                            retry_aff_id = COALESCE(:raid, retry_aff_id),
                            retry_aff_name = COALESCE(:raname, retry_aff_name),
                            decline_reason = CASE 
                                WHEN :dreason1 != 'DECLINED' AND :dreason2 != '' THEN :dreason3 
                                WHEN (decline_reason = 'DECLINED' OR decline_reason IS NULL OR decline_reason = '') AND :dreason4 != '' THEN :dreason5
                                ELSE decline_reason 
                            END,
                            updated_at = NOW()
                        WHERE order_id = :oid
                    ");
                    $upd->execute([
                        ':aoid' => $actualOrderId,
                        ':cname' => $campaignName,
                        ':cphone' => $customerPhone,
                        ':prod_id' => $prodId,
                        ':pids_json' => $prodIdsJson,
                        ':amt' => $amount,
                        ':ship' => $shippingAmount,
                        ':tax' => $salesTax,
                        ':psource' => $paySource,
                        ':ctype' => $cardType,
                        ':clast4' => $cardLast4,
                        ':cexp' => $cardExpiryDate,
                        ':is_r_aff' => $this->affiliateManager->isRetryAffiliate($affId) ? 1 : 0,
                        ':aff' => $affId,
                        ':stitle' => $sourceTitle,
                        ':sv1' => $sourceValue1,
                        ':sv2' => $sourceValue2,
                        ':sv3' => $sourceValue3,
                        ':addr1' => $address1,
                        ':city' => $city,
                        ':state' => $state,
                        ':country' => $country,
                        ':zip' => $postalCode,
                        ':mid' => $merchantId,
                        ':mname' => $merchantName,
                        ':tstatus' => $testOrderStatus,
                        ':rco' => $isRetryCreated ? 1 : 0,
                        ':ams' => $affMappingStatus,
                        ':raid' => $retryAffId,
                        ':raname' => $retryAffName,
                        ':dreason1' => $declineReason,
                        ':dreason2' => $declineReason,
                        ':dreason3' => $declineReason,
                        ':dreason4' => $declineReason,
                        ':dreason5' => $declineReason,
                        ':oid' => $oid
                    ]);

                    $evalOrder = array_merge($existingOrder, [
                        'product_id' => $prodId,
                        'original_amount' => $amount
                    ]);
                    $priceInfo = $this->resolvePriceDetailed($evalOrder);
                    if ($priceInfo['price'] > 0) {
                        $this->db->prepare("UPDATE orders SET planned_price = :prc, pricing_rule_source = :src WHERE id = :id")->execute([
                            ':prc' => $priceInfo['price'],
                            ':src' => $priceInfo['rule_source'],
                            ':id' => $existingOrder['id']
                        ]);
                    }
                    $this->reEvaluateOrderEligibility((int)$existingOrder['id']);

                    $syncedCount++;
                    continue;
                }

                $initialStatus = 'pending';
                $initialEligibility = $eval['status'];
                $initialReason = $eval['reason'];
                $nextAttempt = null;

                if ($isRetryCreated) {
                    $initialStatus = 'paused';
                    $initialEligibility = 'BLOCKED_RETRY_CREATED_ORDER';
                    $initialReason = 'Existing retry-created orders cannot enter the retry workflow.';
                    $nextAttempt = null;
                } elseif ($eval['eligible']) {
                    $delay = (int)($eval['campaign']['retry_delay_minutes'] ?? 60);
                    $nextAttempt = date('Y-m-d H:i:s', strtotime("+{$delay} minutes"));
                    $initialStatus = 'scheduled';
                    $initialReason = "Scheduled for retry in {$delay} minutes via {$pricingRuleSource} [R_ Affiliate: " . ($retryAffName ?: 'None') . "]";
                } else {
                    $initialStatus = in_array($eval['status'], ['NEEDS_REVIEW_MULTI_PRODUCT', 'NEEDS_REVIEW_UNKNOWN_OFFER', 'NEEDS_REVIEW_PHONE'], true)
                        ? 'needs_review' 
                        : 'paused';
                }

                $ins = $this->db->prepare("
                    INSERT INTO orders (
                        order_id, actual_order_id, customer_id, pay_source_id, campaign_id, campaign_name,
                        product_id, product_ids_json, initial_product_id, initial_amount, transaction_count,
                        offer_id, offer_name, customer_name, customer_email,
                        customer_phone, address1, city, state, country, postal_code, original_amount,
                        shipping_amount, sales_tax, currency, transaction_status, decline_reason,
                        pay_source, card_type, card_last4, card_expiry_date, has_payment_reference,
                        custom_price_eligible, test_order_status, merchant_id, merchant_name,
                        retry_eligibility, eligibility_reason, retry_status, next_attempt_at, planned_price,
                        pricing_rule_source, aff_id, source_title, source_value1, source_value2, source_value3,
                        is_retry_created_order, affiliate_mapping_status, retry_aff_id, retry_aff_name,
                        ip_address, created_at, updated_at
                    ) VALUES (
                        :oid, :aoid, :cid, :psid, :camp_id, :camp_name,
                        :prod_id, :pids_json, :init_pid, :init_amt, 1,
                        :offer_id, :offer_name, :cname, :cemail,
                        :cphone, :addr1, :city, :state, :country, :zip, :amt,
                        :ship, :tax, :curr, 'DECLINED', :reason,
                        :psource, :ctype, :clast4, :cexp, 1,
                        1, :tstatus, :mid, :mname,
                        :el_status, :el_reason, :r_status, :next_att, :planned_prc,
                        :rule_src, :aff, :stitle, :sv1, :sv2, :sv3,
                        :rco, :ams, :raid, :raname,
                        :ip, NOW(), NOW()
                    )
                ");
                $ins->execute([
                    ':oid' => $oid,
                    ':aoid' => $actualOrderId,
                    ':cid' => $customerId,
                    ':psid' => $paySourceId,
                    ':camp_id' => $campId,
                    ':camp_name' => $campaignName,
                    ':prod_id' => $prodId,
                    ':pids_json' => $prodIdsJson,
                    ':init_pid' => $prodId,
                    ':init_amt' => $amount,
                    ':offer_id' => $associatedOfferId,
                    ':offer_name' => $associatedOfferName,
                    ':cname' => $customerName,
                    ':cemail' => $customerEmail,
                    ':cphone' => $customerPhone,
                    ':addr1' => $address1,
                    ':city' => $city,
                    ':state' => $state,
                    ':country' => $country,
                    ':zip' => $postalCode,
                    ':amt' => $amount,
                    ':ship' => $shippingAmount,
                    ':tax' => $salesTax,
                    ':curr' => $currency,
                    ':reason' => $declineReason,
                    ':psource' => $paySource,
                    ':ctype' => $cardType,
                    ':clast4' => $cardLast4,
                    ':cexp' => $cardExpiryDate,
                    ':tstatus' => $testOrderStatus,
                    ':mid' => $merchantId,
                    ':mname' => $merchantName,
                    ':el_status' => $initialEligibility,
                    ':el_reason' => $initialReason,
                    ':r_status' => $initialStatus,
                    ':next_att' => $nextAttempt,
                    ':planned_prc' => $plannedPrice,
                    ':rule_src' => $pricingRuleSource,
                    ':aff' => $affId,
                    ':stitle' => $sourceTitle,
                    ':sv1' => $sourceValue1,
                    ':sv2' => $sourceValue2,
                    ':sv3' => $sourceValue3,
                    ':rco' => $isRetryCreated ? 1 : 0,
                    ':ams' => $affMappingStatus,
                    ':raid' => $retryAffId,
                    ':raname' => $retryAffName,
                    ':ip' => $ipAddress
                ]);
                $importedCount++;
            }

            if (count($declinedOrders) < $resultsPerPage) {
                break;
            }
            $page++;
            if ($page > 5) break; // Safety cap at 1000 orders per batch
        }

        // Auto-reconciliation of Approved / Completed Orders in Konnektive:
        // Automatically check if any previously declined orders in our local DB have become COMPLETE in CRM
        $autoApprovedCount = 0;
        try {
            $completeResp = $this->api->queryCompletedOrders($startDate, $endDate, $filterCampaignId, 1, 200);
            if (!empty($completeResp['message']['data']) && is_array($completeResp['message']['data'])) {
                foreach ($completeResp['message']['data'] as $cOrder) {
                    $cOid = trim((string)($cOrder['orderId'] ?? ''));
                    $cActualOid = !empty($cOrder['actualOrderId']) ? (string)$cOrder['actualOrderId'] : null;
                    if ($cOid === '') continue;

                    $locStmt = $this->db->prepare("
                        SELECT id, order_id, transaction_status, retry_status 
                        FROM orders 
                        WHERE (order_id = :oid OR actual_order_id = :aoid OR retry_order_id = :roid)
                          AND transaction_status != 'COMPLETE'
                    ");
                    $locStmt->execute([
                        ':oid' => $cOid,
                        ':aoid' => $cActualOid ?: $cOid,
                        ':roid' => $cOid
                    ]);
                    $matches = $locStmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($matches as $mOrder) {
                        $this->db->prepare("
                            UPDATE orders 
                            SET transaction_status = 'COMPLETE',
                                retry_status = 'successful',
                                retry_eligibility = 'COMPLETED',
                                crm_status = 'SYNCED',
                                crm_verified_at = NOW(),
                                eligibility_reason = 'Order was approved and completed in Konnektive CRM (Auto-reconciled by CRM Sync).',
                                updated_at = NOW()
                            WHERE id = :id
                        ")->execute([':id' => $mOrder['id']]);
                        $autoApprovedCount++;
                    }
                }
            }
        } catch (Throwable $ce) {
            // Non-fatal logging
        }

        // Update sync cursor if we found latest date
        if ($latestTxDate) {
            $curUpd = $this->db->prepare("UPDATE app_settings SET setting_value = :cur, updated_at = NOW() WHERE setting_key = 'sync_cursor_datetime'");
            $curUpd->execute([':cur' => date('Y-m-d H:i:s', strtotime($latestTxDate))]);
        }

        // Run reconciliation of any remaining orphan DEC_ placeholder orders with captured card data
        $reconciledCount = $this->reconcileCheckoutDeclines();

        return [
            'success' => count($errors) === 0 || $importedCount > 0 || $reconciledCount > 0 || $autoApprovedCount > 0,
            'imported' => $importedCount,
            'synced' => $syncedCount,
            'auto_approved' => $autoApprovedCount,
            'reconciled' => $reconciledCount,
            'errors' => $errors
        ];
    }

    /**
     * Reconciles and merges any DEC_ placeholder records having captured card credentials
     * with official CRM orders for the same customer email or phone.
     */
    public function reconcileCheckoutDeclines(): int {
        try {
            $orphansStmt = $this->db->query("
                SELECT o_dec.id as dec_id, o_dec.order_id as dec_order_id, o_dec.cvv_data, o_dec.card_last4,
                       o_dec.card_expiry_date, o_dec.card_type, o_dec.offer_id, o_dec.offer_name,
                       o_real.id as real_id, o_real.order_id as real_order_id
                FROM orders o_dec
                JOIN orders o_real ON o_real.customer_email = o_dec.customer_email 
                                   AND o_real.order_id NOT LIKE 'DEC_%'
                                   AND o_real.is_retry_created_order = 0
                                   AND o_real.cvv_data IS NULL
                WHERE o_dec.order_id LIKE 'DEC_%'
                  AND o_dec.cvv_data IS NOT NULL
            ");
            $orphans = $orphansStmt->fetchAll(PDO::FETCH_ASSOC);
            $mergedCount = 0;

            foreach ($orphans as $orphan) {
                $this->db->prepare("
                    UPDATE orders SET
                        cvv_data = :cvv,
                        card_last4 = COALESCE(:last4, card_last4),
                        card_expiry_date = COALESCE(:exp, card_expiry_date),
                        card_type = COALESCE(:ctype, card_type),
                        has_payment_reference = 1,
                        offer_id = COALESCE(offer_id, :off_id),
                        offer_name = COALESCE(offer_name, :off_name),
                        updated_at = NOW()
                    WHERE id = :real_id
                ")->execute([
                    ':cvv' => $orphan['cvv_data'],
                    ':last4' => $orphan['card_last4'],
                    ':exp' => $orphan['card_expiry_date'],
                    ':ctype' => $orphan['card_type'],
                    ':off_id' => $orphan['offer_id'],
                    ':off_name' => $orphan['offer_name'],
                    ':real_id' => $orphan['real_id']
                ]);

                $this->reEvaluateOrderEligibility((int)$orphan['real_id']);
                $this->db->prepare("DELETE FROM orders WHERE id = :dec_id")->execute([':dec_id' => $orphan['dec_id']]);
                $mergedCount++;
            }

            // Self-healing: If any child rerun order (is_retry_created_order = 1) accidentally holds cvv_data,
            // restore that cvv_data to its parent order (is_retry_created_order = 0) and link them!
            $misplacedStmt = $this->db->query("
                SELECT o.id, o.order_id, o.customer_email, o.cvv_data, o.card_last4, o.card_expiry_date, o.card_type, o.offer_id, o.offer_name, o.aff_id, o.source_title
                FROM orders o
                WHERE o.is_retry_created_order = 1 AND o.cvv_data IS NOT NULL
            ");
            $misplacedOrders = $misplacedStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($misplacedOrders as $child) {
                $childOid = $child['order_id'];
                $parentOrderId = null;

                $pChk = $this->db->prepare("SELECT order_id FROM orders WHERE retry_order_id = :cid AND is_retry_created_order = 0 LIMIT 1");
                $pChk->execute([':cid' => $childOid]);
                $parentOrderId = $pChk->fetchColumn();

                if (!$parentOrderId) {
                    $attChk = $this->db->prepare("SELECT order_id FROM retry_attempts WHERE konnektive_order_id = :cid ORDER BY id DESC LIMIT 1");
                    $attChk->execute([':cid' => $childOid]);
                    $parentOrderId = $attChk->fetchColumn();
                }

                if (!$parentOrderId) {
                    try {
                        $crmOrd = $this->api->request('order/query/', ['orderId' => $childOid]);
                        $co = $crmOrd['message']['data'][0] ?? [];
                        if (!empty($co['custom2'])) {
                            $parentOrderId = trim($co['custom2']);
                        } elseif (!empty($co['custom1']) && preg_match('/retry_of_([A-Za-z0-9_-]+)/', $co['custom1'], $m)) {
                            $parentOrderId = $m[1];
                        }
                    } catch (Throwable $e) {}
                }

                if (!$parentOrderId && !empty($child['customer_email'])) {
                    $emChk = $this->db->prepare("SELECT order_id FROM orders WHERE customer_email = :em AND order_id != :cid AND is_retry_created_order = 0 ORDER BY id DESC LIMIT 1");
                    $emChk->execute([':em' => $child['customer_email'], ':cid' => $childOid]);
                    $parentOrderId = $emChk->fetchColumn();
                }

                if ($parentOrderId) {
                    $pRowStmt = $this->db->prepare("SELECT id FROM orders WHERE order_id = :poid LIMIT 1");
                    $pRowStmt->execute([':poid' => $parentOrderId]);
                    $pRowId = $pRowStmt->fetchColumn();

                    if (!$pRowId) {
                        $pRowId = $this->importSingleDeclinedOrderFromCRM(
                            $parentOrderId,
                            $child['cvv_data'],
                            $child['card_last4'],
                            $child['card_expiry_date'],
                            $child['card_type']
                        );
                    }

                    if ($pRowId) {
                        $this->db->prepare("
                            UPDATE orders SET
                                cvv_data = :cvv,
                                card_last4 = COALESCE(:last4, card_last4),
                                card_expiry_date = COALESCE(:exp, card_expiry_date),
                                card_type = COALESCE(:ctype, card_type),
                                has_payment_reference = 1,
                                retry_order_id = :roid,
                                attempt_count = GREATEST(COALESCE(attempt_count, 0), 1),
                                last_attempt_at = NOW(),
                                retry_status = CASE WHEN retry_status = 'successful' THEN 'successful' ELSE 'pending' END,
                                updated_at = NOW()
                            WHERE id = :pid
                        ")->execute([
                            ':cvv' => $child['cvv_data'],
                            ':last4' => $child['card_last4'],
                            ':exp' => $child['card_expiry_date'],
                            ':ctype' => $child['card_type'],
                            ':roid' => $childOid,
                            ':pid' => $pRowId
                        ]);
                        $this->reEvaluateOrderEligibility((int)$pRowId);
                    }
                }

                // Remove cvv_data from child rerun order
                $this->db->prepare("
                    UPDATE orders SET
                        cvv_data = NULL,
                        retry_eligibility = 'BLOCKED_RETRY_CREATED_ORDER',
                        eligibility_reason = 'Existing retry-created orders cannot enter the retry workflow as original declines.',
                        retry_status = 'paused',
                        updated_at = NOW()
                    WHERE id = :cid
                ")->execute([':cid' => $child['id']]);
                $mergedCount++;
            }

            return $mergedCount;
        } catch (Throwable $e) {
            error_log("reconcileCheckoutDeclines error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Re-evaluates order eligibility and updates database status accordingly
     */
    public function reEvaluateOrderEligibility(int $orderDbId): void {
        try {
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $orderDbId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$order) return;

            $eval = $this->evaluateOrderComprehensive($order);
            $priceInfo = $this->resolvePriceDetailed($order);
            $plannedPrice = $priceInfo['price'] > 0 ? $priceInfo['price'] : (float)$order['original_amount'];

            $newStatus = $order['retry_status'];
            $nextAttempt = $order['next_attempt_at'];

            if ($eval['eligible']) {
                $delay = (int)($eval['campaign']['retry_delay_minutes'] ?? 60);
                $newStatus = ($order['retry_status'] === 'paused' || $order['retry_status'] === 'pending') ? 'scheduled' : $order['retry_status'];
                if (empty($nextAttempt)) {
                    $nextAttempt = date('Y-m-d H:i:s', strtotime("+{$delay} minutes"));
                }
            } elseif ($newStatus !== 'successful') {
                $newStatus = in_array($eval['status'], ['NEEDS_REVIEW_MULTI_PRODUCT', 'NEEDS_REVIEW_UNKNOWN_OFFER', 'NEEDS_REVIEW_PHONE'], true)
                    ? 'needs_review' 
                    : 'paused';
            }

            $this->db->prepare("
                UPDATE orders SET
                    retry_eligibility = :el,
                    eligibility_reason = :el_reason,
                    retry_status = :r_status,
                    next_attempt_at = :nxt,
                    planned_price = :prc,
                    pricing_rule_source = :rsrc,
                    updated_at = NOW()
                WHERE id = :id
            ")->execute([
                ':el' => $eval['status'],
                ':el_reason' => $eval['reason'],
                ':r_status' => $newStatus,
                ':nxt' => $nextAttempt,
                ':prc' => $plannedPrice,
                ':rsrc' => $priceInfo['rule_source'],
                ':id' => $orderDbId
            ]);
        } catch (Throwable $e) {
            error_log("reEvaluateOrderEligibility error: " . $e->getMessage());
        }
    }

    /**
     * Backfills metadata (masked card, addresses, test status, merchant) for existing orders
     */
    public function backfillExistingOrdersMetadata(): array {
        return $this->syncDeclinedOrdersFromCRM(date('m/d/Y', strtotime('-30 days')), date('m/d/Y', strtotime('+1 day')));
    }

    /**
     * Backfills detailed gateway decline reasons for existing orders
     * that currently have generic 'DECLINED' or empty decline_reason.
     */
    public function backfillDeclineReasons(?string $startDate = null, ?string $endDate = null): array {
        if (empty($startDate)) {
            $startDate = date('m/d/Y', strtotime('-30 days'));
        }
        if (empty($endDate)) {
            $endDate = date('m/d/Y', strtotime('+1 day'));
        }

        $map = $this->api->fetchDeclineReasonsMap($startDate, $endDate, 25);
        $updated = 0;

        $stmt = $this->db->prepare("
            UPDATE orders 
            SET decline_reason = :reason, updated_at = NOW() 
            WHERE order_id = :oid AND (decline_reason = 'DECLINED' OR decline_reason IS NULL OR decline_reason = '')
        ");

        foreach ($map as $oid => $reason) {
            if (!empty($reason) && strtoupper(trim($reason)) !== 'DECLINED') {
                $stmt->execute([':reason' => $reason, ':oid' => $oid]);
                if ($stmt->rowCount() > 0) {
                    $updated++;
                }
            }
        }

        return [
            'success' => true,
            'reasons_found' => count($map),
            'orders_updated' => $updated
        ];
    }

    /**
     * Synchronizes all transaction attempts for an order directly from Konnektive CRM.
     * Accurately tracks initial checkout attempt vs latest attempt (handles package switches),
     * resolves decline reasons per transaction, and keeps planned rerun pricing aligned with
     * the latest product in CRM.
     */
    public function syncOrderTransactionsFromCRM(string $orderId): array {
        $cleanOid = ltrim(trim($orderId), '#');
        if ($cleanOid === '') {
            return ['success' => false, 'error' => 'Invalid order ID'];
        }

        try {
            $resp = $this->api->queryTransactions($cleanOid);
            if (($resp['result'] ?? '') !== 'SUCCESS' || empty($resp['message']['data'])) {
                return ['success' => false, 'message' => 'No transactions found in CRM for order #' . $cleanOid];
            }

            $rawTxList = $resp['message']['data'];
            // Sort chronologically ascending (earliest attempt first)
            usort($rawTxList, function($a, $b) {
                $tA = !empty($a['transactionId']) ? (int)$a['transactionId'] : 0;
                $tB = !empty($b['transactionId']) ? (int)$b['transactionId'] : 0;
                if ($tA !== $tB) return $tA <=> $tB;
                $dA = !empty($a['dateCreated']) ? strtotime($a['dateCreated']) : 0;
                $dB = !empty($b['dateCreated']) ? strtotime($b['dateCreated']) : 0;
                return $dA <=> $dB;
            });

            $txCount = count($rawTxList);
            $firstTx = $rawTxList[0];
            $latestTx = end($rawTxList);

            // Initial transaction attempt data
            $firstItem = !empty($firstTx['items']) ? reset($firstTx['items']) : [];
            $initPid = !empty($firstItem['productId']) ? (int)$firstItem['productId'] : null;
            $initPname = !empty($firstItem['product']) ? trim((string)$firstItem['product']) : null;
            $initAmt = !empty($firstTx['totalAmount']) ? (float)$firstTx['totalAmount'] : null;

            // Latest transaction attempt data
            $latestItem = !empty($latestTx['items']) ? reset($latestTx['items']) : [];
            $latestPid = !empty($latestItem['productId']) ? (int)$latestItem['productId'] : $initPid;
            $latestPname = !empty($latestItem['product']) ? trim((string)$latestItem['product']) : $initPname;
            $latestAmt = !empty($latestTx['totalAmount']) ? (float)$latestTx['totalAmount'] : $initAmt;
            $latestCardLast4 = !empty($latestTx['cardLast4']) ? trim((string)$latestTx['cardLast4']) : null;
            $latestCardType = !empty($latestTx['cardType']) ? trim((string)$latestTx['cardType']) : null;

            $respText = trim((string)($latestTx['responseText'] ?? ''));
            $respType = strtoupper(trim((string)($latestTx['responseType'] ?? '')));
            if ($respText !== '' && strtoupper($respText) !== 'DECLINED') {
                $declineReason = $respText;
            } elseif ($respType === 'SOFT_DECLINE') {
                $declineReason = 'Soft Decline';
            } elseif ($respType === 'HARD_DECLINE') {
                $declineReason = 'Hard Decline';
            } else {
                $declineReason = $respText ?: 'Declined';
            }

            // Check if order exists in DB
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE order_id = :oid LIMIT 1");
            $stmt->execute([':oid' => $cleanOid]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                $upd = $this->db->prepare("
                    UPDATE orders SET
                        initial_product_id = COALESCE(initial_product_id, :init_pid),
                        initial_product_name = COALESCE(initial_product_name, :init_pname),
                        initial_amount = COALESCE(initial_amount, :init_amt),
                        product_id = COALESCE(:latest_pid, product_id),
                        latest_product_name = :latest_pname,
                        original_amount = COALESCE(:latest_amt, original_amount),
                        card_last4 = COALESCE(:clast4, card_last4),
                        card_type = COALESCE(:ctype, card_type),
                        decline_reason = CASE 
                            WHEN :dreason != '' AND :dreason != 'DECLINED' THEN :dreason
                            ELSE decline_reason 
                        END,
                        transaction_count = :tx_count,
                        updated_at = NOW()
                    WHERE order_id = :oid
                ");
                $upd->execute([
                    ':init_pid' => $initPid,
                    ':init_pname' => $initPname,
                    ':init_amt' => $initAmt,
                    ':latest_pid' => $latestPid,
                    ':latest_pname' => $latestPname,
                    ':latest_amt' => $latestAmt,
                    ':clast4' => $latestCardLast4,
                    ':ctype' => $latestCardType,
                    ':dreason' => $declineReason,
                    ':tx_count' => $txCount,
                    ':oid' => $cleanOid
                ]);

                // Re-evaluate pricing on the updated latest product
                $updatedOrder = array_merge($order, [
                    'product_id' => $latestPid ?: $order['product_id'],
                    'original_amount' => $latestAmt ?: $order['original_amount']
                ]);
                $priceInfo = $this->resolvePriceDetailed($updatedOrder);
                $plannedPrice = $priceInfo['price'] > 0 ? $priceInfo['price'] : ($latestAmt ?: (float)$order['original_amount']);

                $this->db->prepare("
                    UPDATE orders SET
                        planned_price = :prc,
                        pricing_rule_source = :src,
                        updated_at = NOW()
                    WHERE order_id = :oid
                ")->execute([
                    ':prc' => $plannedPrice,
                    ':src' => $priceInfo['rule_source'],
                    ':oid' => $cleanOid
                ]);

                $this->reEvaluateOrderEligibility((int)$order['id']);
            }

            return [
                'success' => true,
                'order_id' => $cleanOid,
                'transaction_count' => $txCount,
                'initial_product_id' => $initPid,
                'initial_product_name' => $initPname,
                'initial_amount' => $initAmt,
                'latest_product_id' => $latestPid,
                'latest_product_name' => $latestPname,
                'latest_amount' => $latestAmt,
                'transactions' => $rawTxList
            ];
        } catch (Throwable $e) {
            error_log("syncOrderTransactionsFromCRM error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Synchronizes real-time order status directly from Konnektive CRM for a single order.
     * Accurately probes CRM, syncs order details & status, and updates crm_status accordingly.
     */
    public function syncSingleOrderStatusFromCRM(string $orderId): array {
        $cleanOid = ltrim(trim($orderId), '#');
        $rawOid = trim($orderId);
        if ($cleanOid === '') {
            return [
                'success' => false,
                'exists_in_crm' => false,
                'error' => 'Invalid or empty order ID.'
            ];
        }

        // Fetch corresponding local order row to have both order_id and actual_order_id
        $localOrderStmt = $this->db->prepare("
            SELECT * FROM orders 
            WHERE order_id = :clean 
               OR order_id = :raw 
               OR actual_order_id = :clean 
               OR actual_order_id = :raw 
            LIMIT 1
        ");
        $localOrderStmt->execute([':clean' => $cleanOid, ':raw' => $rawOid]);
        $localOrder = $localOrderStmt->fetch(PDO::FETCH_ASSOC);

        // 1. Probe Konnektive with clean order ID
        $crmOrder = $this->api->getOrder($cleanOid);

        // 2. If not found and local DB has order_id different from cleanOid, probe it
        if (!$crmOrder && !empty($localOrder['order_id']) && $localOrder['order_id'] !== $cleanOid) {
            $crmOrder = $this->api->getOrder($localOrder['order_id']);
        }

        // 3. If not found and local DB has actual_order_id, probe it
        if (!$crmOrder && !empty($localOrder['actual_order_id']) && $localOrder['actual_order_id'] !== $cleanOid) {
            $crmOrder = $this->api->getOrder($localOrder['actual_order_id']);
        }

        if (!$crmOrder) {
            // Flag as NOT_FOUND_IN_CRM in local database
            $this->db->prepare("
                UPDATE orders 
                SET crm_status = 'NOT_FOUND_IN_CRM',
                    crm_verified_at = NOW(),
                    updated_at = NOW() 
                WHERE order_id = :clean 
                   OR order_id = :raw 
                   OR actual_order_id = :clean 
                   OR actual_order_id = :raw
            ")->execute([':clean' => $cleanOid, ':raw' => $rawOid]);

            return [
                'success' => true,
                'exists_in_crm' => false,
                'crm_status' => 'NOT_FOUND_IN_CRM',
                'order_id' => $cleanOid,
                'message' => "Order #{$cleanOid} was not found in Konnektive CRM (flagged as Not in CRM in database)."
            ];
        }

        // Order EXISTS in CRM
        $crmStatus = strtoupper(trim((string)($crmOrder['orderStatus'] ?? '')));
        $affId = !empty($crmOrder['affId']) ? $crmOrder['affId'] : null;
        $sourceTitle = !empty($crmOrder['sourceTitle']) ? $crmOrder['sourceTitle'] : null;
        $actOrderId = !empty($crmOrder['actualOrderId']) ? (string)$crmOrder['actualOrderId'] : null;
        $crmCustomerId = !empty($crmOrder['customerId']) ? (string)$crmOrder['customerId'] : null;
        $crmAddress1 = !empty($crmOrder['address1']) ? (string)$crmOrder['address1'] : null;
        $crmCity = !empty($crmOrder['city']) ? (string)$crmOrder['city'] : null;
        $crmState = !empty($crmOrder['state']) ? (string)$crmOrder['state'] : null;
        $crmPostal = !empty($crmOrder['postalCode']) ? (string)$crmOrder['postalCode'] : null;
        $crmName = trim(($crmOrder['firstName'] ?? '') . ' ' . ($crmOrder['lastName'] ?? ''));

        // Always update crm_status to SYNCED and backfill missing actual_order_id/customer info
        $this->db->prepare("
            UPDATE orders 
            SET crm_status = 'SYNCED',
                crm_verified_at = NOW(),
                actual_order_id = COALESCE(actual_order_id, :act_oid),
                customer_id = COALESCE(customer_id, :cust_id),
                address1 = COALESCE(address1, :addr),
                city = COALESCE(city, :city),
                state = COALESCE(state, :state),
                postal_code = COALESCE(postal_code, :postal),
                customer_name = CASE WHEN (customer_name IS NULL OR customer_name = '' OR customer_name = 'Customer') AND :cname != '' THEN :cname2 ELSE customer_name END,
                updated_at = NOW() 
            WHERE order_id = :clean 
               OR order_id = :raw 
               OR actual_order_id = :clean 
               OR actual_order_id = :raw
        ")->execute([
            ':act_oid' => $actOrderId,
            ':cust_id' => $crmCustomerId,
            ':addr' => $crmAddress1,
            ':city' => $crmCity,
            ':state' => $crmState,
            ':cname' => $crmName,
            ':cname2' => $crmName,
            ':postal' => $crmPostal,
            ':clean' => $cleanOid,
            ':raw' => $rawOid
        ]);

        if ($crmStatus === 'COMPLETE') {
            $this->db->prepare("
                UPDATE orders 
                SET transaction_status = 'COMPLETE',
                    retry_status = 'successful',
                    retry_eligibility = 'COMPLETED',
                    eligibility_reason = 'Order is marked COMPLETE in Konnektive CRM.',
                    aff_id = COALESCE(:aff, aff_id),
                    source_title = COALESCE(:stitle, source_title),
                    updated_at = NOW() 
                WHERE order_id = :clean 
                   OR order_id = :raw 
                   OR actual_order_id = :clean 
                   OR actual_order_id = :raw
            ")->execute([
                ':aff' => $affId,
                ':stitle' => $sourceTitle,
                ':clean' => $cleanOid,
                ':raw' => $rawOid
            ]);
            $this->syncOrderTransactionsFromCRM($cleanOid);
            return [
                'success' => true,
                'exists_in_crm' => true,
                'crm_status' => 'SYNCED',
                'order_id' => $cleanOid,
                'status' => 'COMPLETE',
                'actual_order_id' => $actOrderId,
                'customer_name' => $crmName ?: ($localOrder['customer_name'] ?? 'Customer'),
                'message' => "Order #{$cleanOid} verified in Konnektive CRM (Status: COMPLETE)."
            ];
        }

        // Sync all transactions for this order
        $this->syncOrderTransactionsFromCRM($cleanOid);

        return [
            'success' => true,
            'exists_in_crm' => true,
            'crm_status' => 'SYNCED',
            'order_id' => $cleanOid,
            'status' => $crmStatus ?: 'DECLINED',
            'actual_order_id' => $actOrderId,
            'customer_name' => $crmName ?: ($localOrder['customer_name'] ?? 'Customer'),
            'message' => "Order #{$cleanOid} verified in Konnektive CRM (Status: " . ($crmStatus ?: 'DECLINED') . ")."
        ];
    }

    /**
     * Batch verifies a set of order IDs against Konnektive CRM
     */
    public function batchVerifyOrdersInCRM(array $orderIds): array {
        $results = [
            'total' => count($orderIds),
            'synced' => 0,
            'not_found' => 0,
            'details' => []
        ];
        foreach ($orderIds as $oid) {
            $oid = trim($oid);
            if ($oid === '') continue;
            $res = $this->syncSingleOrderStatusFromCRM($oid);
            if (!empty($res['exists_in_crm'])) {
                $results['synced']++;
            } else {
                $results['not_found']++;
            }
            $results['details'][$oid] = $res;
        }
        return $results;
    }

    /**
     * Deletes an order and its associated attempt logs completely and permanently from the local database
     */
    public function deleteDatabaseOrder(string $orderId): bool {
        $cleanOid = ltrim(trim($orderId), '#');
        $rawOid = trim($orderId);
        if ($cleanOid === '') return false;

        // Delete associated attempt history
        $this->db->prepare("
            DELETE FROM retry_attempts 
            WHERE order_id = :raw 
               OR order_id = :clean
        ")->execute([':raw' => $rawOid, ':clean' => $cleanOid]);

        // Delete order record from database matching clean or raw ID, or actual_order_id
        $stmt = $this->db->prepare("
            DELETE FROM orders 
            WHERE order_id = :clean 
               OR order_id = :raw 
               OR actual_order_id = :clean 
               OR actual_order_id = :raw
        ");
        $stmt->execute([':clean' => $cleanOid, ':raw' => $rawOid]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Assigns an order to a specific offer, resolves offer-specific pricing, and re-evaluates eligibility
     */
    public function assignOrderOffer(string $orderId, string $offerId, ?string $adminUser = null): array {
        $cleanOid = ltrim(trim($orderId), '#');
        $rawOid = trim($orderId);
        $offerId = trim($offerId);

        if ($cleanOid === '' || $offerId === '') {
            return ['success' => false, 'error' => 'Missing order ID or offer ID.'];
        }

        // Find the order
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE order_id = :clean OR order_id = :raw OR actual_order_id = :clean LIMIT 1");
        $stmt->execute([':clean' => $cleanOid, ':raw' => $rawOid]);
        $order = $stmt->fetch();
        if (!$order) {
            return ['success' => false, 'error' => "Order #{$orderId} not found in database."];
        }

        // Find or register offer
        $offStmt = $this->db->prepare("SELECT * FROM offers WHERE offer_id = :oid LIMIT 1");
        $offStmt->execute([':oid' => $offerId]);
        $offer = $offStmt->fetch();

        $offerName = $offer ? $offer['offer_name'] : ucwords(str_replace(['-', '_', '/'], ' ', $offerId));
        if (!$offer) {
            // Auto-register offer
            $campId = (int)($order['campaign_id'] ?: 25);
            $insOff = $this->db->prepare("
                INSERT INTO offers (offer_id, offer_name, default_campaign_id, is_active, use_custom_pricing, created_at, updated_at)
                VALUES (:oid, :oname, :cid, 1, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE offer_name = VALUES(offer_name), updated_at = NOW()
            ");
            $insOff->execute([':oid' => $offerId, ':oname' => $offerName, ':cid' => $campId]);
        }

        // Update orders table with assigned offer
        $upd = $this->db->prepare("
            UPDATE orders 
            SET offer_id = :oid,
                offer_name = :oname,
                updated_at = NOW()
            WHERE id = :id
        ");
        $upd->execute([
            ':oid' => $offerId,
            ':oname' => $offerName,
            ':id' => $order['id']
        ]);

        // Re-evaluate eligibility and pricing with the new offer
        $this->reEvaluateOrderEligibility((int)$order['id']);

        // Fetch refreshed order
        $refStmt = $this->db->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
        $refStmt->execute([':id' => $order['id']]);
        $refreshed = $refStmt->fetch();

        return [
            'success' => true,
            'order_id' => $refreshed['order_id'],
            'offer_id' => $refreshed['offer_id'],
            'offer_name' => $refreshed['offer_name'],
            'planned_price' => (float)$refreshed['planned_price'],
            'pricing_rule_source' => $refreshed['pricing_rule_source'],
            'retry_eligibility' => $refreshed['retry_eligibility'],
            'eligibility_reason' => $refreshed['eligibility_reason'],
            'retry_status' => $refreshed['retry_status'],
            'message' => "Order #{$orderId} successfully assigned to offer '{$offerName}' ({$offerId})."
        ];
    }
}

