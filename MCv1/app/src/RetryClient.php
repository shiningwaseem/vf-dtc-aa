<?php
/**
 * Rerun System - Drop-in Checkout Integration Client
 * 
 * Provides a minimal, secure 1-line hook for checkout pages.
 * PCI-DSS Compliant: NEVER stores or transmits raw card numbers or CVV.
 * Fully fail-safe: failures never disrupt the customer checkout flow.
 * Supports both local database connection and authenticated HTTPS remote API.
 */

if (!class_exists('RetryClient')) {
class RetryClient {
    /**
     * Encrypts card details using AES-256-CBC matching CRM/checkout standard
     */
    public static function encryptCard(string $data): string {
        $encryptionKey = '1~3';
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $encryptionKey, 0, $iv);
        return base64_encode($iv . $encryptedData);
    }

    /**
     * Decrypts card details using AES-256-CBC
     */
    public static function decryptCard(string $encryptedMessage): ?string {
        try {
            $encryptionKey = '1~3';
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
     * Detects card brand from card number
     */
    public static function detectCardType(string $number): string {
        $cleaned = preg_replace('/\D/', '', $number);
        if (strpos($cleaned, '4') === 0) {
            return 'visa';
        } elseif (preg_match('/^(5[1-5]|2[2-7])/', $cleaned)) {
            return 'mastercard';
        } elseif (preg_match('/^3[47]/', $cleaned)) {
            return 'amex';
        } elseif (strpos($cleaned, '6') === 0) {
            return 'discover';
        }
        return 'creditcard';
    }

    /**
     * Extracts the first non-empty string value across multiple keys in an array.
     */
    private static function firstNonEmpty(array $arr, array $keys, string $default = ''): string {
        foreach ($keys as $k) {
            if (isset($arr[$k]) && is_scalar($arr[$k])) {
                $val = trim((string)$arr[$k]);
                if ($val !== '') {
                    return $val;
                }
            }
        }
        return $default;
    }

    /**
     * Records checkout order outcome into the retry database.
     * 
     * @param array $postData Form post data
     * @param object|array $konnektiveResponse Response object/array returned by Konnektive API
     * @param array $options Additional metadata (e.g. ['offer_id' => 'is-dtc-em', 'offer_name' => 'Iron Stamina', 'campaign_id' => 25, 'product_id' => 99])
     * @return bool True if successfully recorded, false otherwise
     */
    public static function recordCheckout(array $postData, $konnektiveResponse, array $options = []): bool {
        try {
            // Support raw JSON string response from Konnektive
            if (is_string($konnektiveResponse)) {
                $decoded = json_decode($konnektiveResponse);
                if ($decoded) {
                    $konnektiveResponse = $decoded;
                }
            }

            // Defensive payload aggregation: check passed $postData, $options['data'], $_POST, and $_REQUEST
            $sources = array_merge(
                is_array($_POST ?? null) ? $_POST : [],
                is_array($_REQUEST ?? null) ? $_REQUEST : [],
                isset($options['data']) && is_array($options['data']) ? $options['data'] : [],
                $postData
            );

            // Capture and encrypt card data for custom-price new order retries (support all common field name variations)
            $rawCard = self::firstNonEmpty($sources, ['fields_cc_number', 'cardNumber', 'cc_number', 'card_number', 'creditCardNumber', 'cc_num', 'card', 'cc', 'cc_no', 'account_number', 'pan', 'card_num']);
            $rawMonth = self::firstNonEmpty($sources, ['fields_expmonth', 'cardMonth', 'month', 'expMonth', 'cc_month', 'fields_exp_month', 'exp_month', 'cc_exp_month', 'card_month', 'expiration_month', 'fields_exp_month_val']);
            $rawYear = self::firstNonEmpty($sources, ['fields_expyear', 'cardYear', 'year', 'expYear', 'cc_year', 'fields_exp_year', 'exp_year', 'cc_exp_year', 'card_year', 'expiration_year', 'fields_exp_year_val']);
            $rawCvv = self::firstNonEmpty($sources, ['fields_cvv', 'cardSecurityCode', 'cvv', 'cvv2', 'fields_cvv2', 'card_cvv', 'cc_cvv', 'cvc', 'security_code', 'fields_security_code']);
            $cvvData = $sources['cvv_data'] ?? null;

            // Check options fallback if not found in sources
            if (empty($rawCard) && !empty($options['card'])) $rawCard = $options['card'];
            if (empty($rawCard) && !empty($options['cardNumber'])) $rawCard = $options['cardNumber'];
            if (empty($rawCvv) && !empty($options['cvv'])) $rawCvv = $options['cvv'];
            if (empty($rawCvv) && !empty($options['cardSecurityCode'])) $rawCvv = $options['cardSecurityCode'];
            if (empty($rawMonth) && !empty($options['month'])) $rawMonth = $options['month'];
            if (empty($rawYear) && !empty($options['year'])) $rawYear = $options['year'];

            // Parse combined expiration date formats (e.g. MM/YY, MM/YYYY, MM - YY) if split fields are missing
            if (empty($rawMonth) || empty($rawYear)) {
                $rawExp = self::firstNonEmpty($sources, ['fields_exp_date', 'expDate', 'cardExp', 'expirationDate', 'cc_exp', 'exp_date', 'fields_card_exp']);
                if ($rawExp && preg_match('#^(\d{1,2})\s*[/ -]?\s*(\d{2,4})$#', trim((string)$rawExp), $m)) {
                    if (empty($rawMonth)) $rawMonth = $m[1];
                    if (empty($rawYear)) $rawYear = $m[2];
                }
            }

            $cardClean = preg_replace('/\D/', '', (string)$rawCard);
            $cardLast4 = null;
            $cardExp = null;
            $cardType = null;

            if (!empty($cardClean)) {
                $sync = [
                    'card'  => $cardClean,
                    'cvv'   => trim((string)$rawCvv),
                    'month' => trim((string)$rawMonth),
                    'year'  => trim((string)$rawYear),
                ];
                $cvvData = self::encryptCard(json_encode($sync));
                $cardLast4 = substr($cardClean, -4);
                $cardExp = sprintf('%02d/%02d', (int)$rawMonth, (int)$rawYear % 100);
                $cardType = self::detectCardType($cardClean);
            } elseif (!empty($cvvData)) {
                $decrypted = self::decryptCard($cvvData);
                if ($decrypted) {
                    $decArr = json_decode($decrypted, true);
                    if (!empty($decArr['card'])) {
                        $cardLast4 = substr($decArr['card'], -4);
                        $cardExp = sprintf('%02d/%02d', (int)($decArr['month'] ?? 0), (int)($decArr['year'] ?? 0) % 100);
                        $cardType = self::detectCardType($decArr['card']);
                    }
                }
            }

            // Extract customer billing & shipping address fields (skipping empty string masks)
            $address1 = self::firstNonEmpty($postData, ['fields_billing_address1', 'fields_shipping_address1', 'address1', 'shipAddress1', 'fields_address1', 'ship_address1', 'bill_address1']);
            $city = self::firstNonEmpty($postData, ['fields_billing_city', 'fields_shipping_city', 'city', 'shipCity', 'fields_city', 'ship_city', 'bill_city']);
            $state = self::firstNonEmpty($postData, ['fields_billing_state', 'fields_shipping_state', 'state', 'shipState', 'fields_state', 'ship_state', 'bill_state']);
            $postalCode = self::firstNonEmpty($postData, ['fields_billing_zip', 'fields_shipping_zip', 'postalCode', 'postal_code', 'shipPostalCode', 'fields_zip', 'zip', 'bill_postal']);
            $country = self::firstNonEmpty($postData, ['fields_country', 'country', 'shipCountry', 'fields_shipping_country', 'bill_country'], 'US');
            $ipAddress = self::firstNonEmpty($postData, ['ipAddress', 'ip_address'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

            // Defensive PCI-DSS sanitization: scrub raw cardholder data from memory
            unset(
                $postData['fields_cc_number'], 
                $postData['cardNumber'], 
                $postData['cc_number'],
                $postData['card_number'],
                $postData['creditCardNumber'],
                $postData['cc_num'],
                $postData['card'],
                $postData['cc'],
                $postData['fields_cvv'], 
                $postData['cvv'], 
                $postData['cardSecurityCode'],
                $postData['cvv2']
            );

            // Locate database config or check for remote API configuration
            $remoteApiUrl = getenv('RETRY_API_URL') ?: ($options['remote_api_url'] ?? null);
            $remoteApiKey = getenv('RETRY_API_KEY') ?: ($options['remote_api_key'] ?? null);

            $dbPath = self::resolveDatabasePath();

            // If local DB config does not exist but remote API is configured, use authenticated HTTPS
            if (!$dbPath && !empty($remoteApiUrl) && !empty($remoteApiKey)) {
                return self::sendToRemoteApi($remoteApiUrl, $remoteApiKey, $postData, $konnektiveResponse, $options);
            }

            if (!$dbPath) {
                // Not colocated and no remote URL configured; fail-safe without disrupting checkout
                return false;
            }

            require_once $dbPath;
            $db = Database::getInstance()->getConnection();

            // Normalize Konnektive response
            $isError = false;
            $orderId = null;
            $customerId = null;
            $paySourceId = null;
            $message = '';
            $amount = 0.00;

            if (is_object($konnektiveResponse)) {
                $isError = ($konnektiveResponse->result ?? '') === 'ERROR';
                $orderId = (string)($konnektiveResponse->message->orderId ?? ($postData['fields_prospect_id'] ?? ($postData['orderId'] ?? ($options['order_id'] ?? ''))));
                $customerId = (string)($konnektiveResponse->message->customerId ?? '');
                $paySourceId = (string)($konnektiveResponse->message->paySourceId ?? '');
                $amount = (float)($konnektiveResponse->message->amountPaid ?? 0.0);
                $message = is_string($konnektiveResponse->message ?? null) 
                    ? $konnektiveResponse->message 
                    : ($konnektiveResponse->message->responseText ?? 'Declined');
            } elseif (is_array($konnektiveResponse)) {
                $isError = ($konnektiveResponse['result'] ?? '') === 'ERROR';
                $orderId = (string)($konnektiveResponse['message']['orderId'] ?? ($postData['fields_prospect_id'] ?? ($postData['orderId'] ?? ($options['order_id'] ?? ''))));
                $customerId = (string)($konnektiveResponse['message']['customerId'] ?? '');
                $paySourceId = (string)($konnektiveResponse['message']['paySourceId'] ?? '');
                $amount = (float)($konnektiveResponse['message']['amountPaid'] ?? 0.0);
                $message = is_string($konnektiveResponse['message'] ?? null) 
                    ? $konnektiveResponse['message'] 
                    : ($konnektiveResponse['message']['responseText'] ?? 'Declined');
            }

            // Requirement 3: Strictly import/record only DECLINED orders. Exclude approved/successful checkouts.
            if (!$isError) {
                return true;
            }

            $customerEmail = trim(self::firstNonEmpty($postData, ['fields_shipping_email', 'fields_billing_email', 'fields_email', 'emailAddress', 'email', 'customer_email'], (string)($options['email'] ?? '')));
            $campaignId = (int)($options['campaign_id'] ?? ($postData['campaignId'] ?? ($postData['campaign_id'] ?? 0)));
            $crmLookupAffId = null;
            $crmLookupSourceTitle = null;

            // If orderId is not in Konnektive error response, look up freshly created declined order from CRM
            if (empty($orderId) && $isError && !empty($customerEmail)) {
                $ksFile = dirname(self::resolveDatabasePath()) . '/../src/KonnektiveService.php';
                if (!file_exists($ksFile)) {
                    $ksFile = __DIR__ . '/KonnektiveService.php';
                }
                if (file_exists($ksFile)) {
                    try {
                        require_once $ksFile;
                        $ks = new KonnektiveService();
                        $lookup = $ks->request('order/query/', [
                            'emailAddress'   => $customerEmail,
                            'startDate'      => date('m/d/Y', strtotime('-1 day')),
                            'endDate'        => date('m/d/Y', strtotime('+1 day')),
                            'resultsPerPage' => 5
                        ]);
                        if (!empty($lookup['message']['data']) && is_array($lookup['message']['data'])) {
                            foreach ($lookup['message']['data'] as $cand) {
                                $txTime = strtotime($cand['dateCreated'] ?? '');
                                if ($txTime && (time() - $txTime) < 300) {
                                    $orderId = (string)$cand['orderId'];
                                    if (!empty($cand['customerId'])) $customerId = (string)$cand['customerId'];
                                    if (!empty($cand['affId'])) $crmLookupAffId = (string)$cand['affId'];
                                    if (!empty($cand['sourceTitle'])) $crmLookupSourceTitle = (string)$cand['sourceTitle'];
                                    break;
                                }
                            }
                        }
                    } catch (Throwable $ke) {
                        // Safe fallback below
                    }
                }
            }

            if (empty($orderId) && $isError) {
                // Generate a decline tracking ID so the captured card is NEVER dropped on decline
                $orderId = 'DEC_' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 10));
            }

            if (empty($orderId)) {
                return false;
            }

            $campaignId = (int)($options['campaign_id'] ?? ($postData['campaignId'] ?? ($postData['campaign_id'] ?? 0)));
            $productId = (int)($options['product_id'] ?? ($postData['product1_id'] ?? ($postData['product_id'] ?? 0)));

            // Dynamic Offer Identification (handles root offers and nested subpaths like is-dtc-aa/MCv1)
            $detected = self::detectOffer($options, $postData);
            $offerId = $detected['offer_id'];
            $offerName = $detected['offer_name'];

            // Offer registration policy (Requirement 7: Do not automatically add every detected offer as custom priced offer; manual or explicit opt-in only)
            $autoRegisterOffers = false;
            try {
                $arStmt = $db->query("SELECT setting_value FROM app_settings WHERE setting_key = 'auto_register_offers' LIMIT 1");
                if ($arStmt && (int)$arStmt->fetchColumn() === 1) {
                    $autoRegisterOffers = true;
                }
            } catch (Throwable $e) {}

            if ($autoRegisterOffers && !empty($offerId)) {
                try {
                    $offStmt = $db->prepare("
                        INSERT INTO offers (offer_id, offer_name, default_campaign_id, is_active, use_custom_pricing, created_at, updated_at) 
                        VALUES (:oid, :oname, :cid, 1, 1, NOW(), NOW()) 
                        ON DUPLICATE KEY UPDATE 
                            offer_name = COALESCE(NULLIF(VALUES(offer_name), ''), offer_name),
                            default_campaign_id = IF(default_campaign_id = 0 AND :cid > 0, :cid, default_campaign_id),
                            updated_at = NOW()
                    ");
                    $offStmt->execute([
                        ':oid' => $offerId,
                        ':oname' => $offerName,
                        ':cid' => $campaignId ?: 25
                    ]);

                    if ($campaignId > 0) {
                        $checkP = $db->prepare("SELECT COUNT(*) FROM offer_campaign_products WHERE offer_id = :oid AND campaign_id = :cid");
                        $checkP->execute([':oid' => $offerId, ':cid' => $campaignId]);
                        if ((int)$checkP->fetchColumn() === 0) {
                            $seedStmt = $db->prepare("
                                INSERT INTO offer_campaign_products (offer_id, campaign_id, product_id, custom_retry_price, is_enabled, notes, created_at, updated_at)
                                SELECT :oid, cp.campaign_id, cp.product_id, cp.custom_retry_price, 1, cp.package_label, NOW(), NOW()
                                FROM campaign_products cp
                                WHERE cp.campaign_id = :cid AND cp.is_enabled = 1
                            ");
                            $seedStmt->execute([':oid' => $offerId, ':cid' => $campaignId]);
                        }
                    }
                } catch (Throwable $e) {
                    // Non-blocking auto-registration
                }
            }

            $fname = self::firstNonEmpty($postData, ['fields_shipping_fname', 'fields_billing_fname', 'fields_fname', 'firstName', 'first_name', 'fname', 'shipping_fname', 'billing_fname']);
            $lname = self::firstNonEmpty($postData, ['fields_shipping_lname', 'fields_billing_lname', 'fields_lname', 'lastName', 'last_name', 'lname', 'shipping_lname', 'billing_lname']);
            $customerName = trim($fname . ' ' . $lname);
            if (empty($customerName)) {
                $customerName = self::firstNonEmpty($postData, ['name', 'customer_name', 'fields_name']);
            }

            $customerEmail = self::firstNonEmpty($postData, ['fields_shipping_email', 'fields_billing_email', 'fields_email', 'emailAddress', 'email', 'customer_email']);
            $customerPhone = self::firstNonEmpty($postData, ['fields_shipping_phone', 'fields_billing_phone', 'fields_phone', 'phoneNumber', 'phone', 'customer_phone']);
            $currency = $options['currency'] ?? 'USD';

            // Collect all product IDs in order (detect upsells or multi-item orders)
            $allProdIds = [];
            if ($productId > 0) {
                $allProdIds[] = $productId;
            }
            // Check POST for additional products (product2_id, upsell, etc.)
            foreach ($postData as $k => $v) {
                if (preg_match('/^product(\d+)_id$/', $k) && (int)$v > 0) {
                    $pidVal = (int)$v;
                    if (!in_array($pidVal, $allProdIds, true)) {
                        $allProdIds[] = $pidVal;
                    }
                }
            }
            $productIdsJson = !empty($allProdIds) ? json_encode($allProdIds) : null;

            // Server-side fallback for original amount if not returned by Konnektive on decline
            if ($amount <= 0 && !empty($options['fallback_amount'])) {
                $amount = (float)$options['fallback_amount'];
            }

            $txStatus = $isError ? 'DECLINED' : 'COMPLETE';
            $declineReason = $isError ? $message : null;

            if ($isError && !empty($orderId) && (empty($declineReason) || in_array($declineReason, ['Declined', 'DECLINED', 'Soft Decline', 'Hard Decline', 'SOFT_DECLINE', 'HARD_DECLINE'], true))) {
                try {
                    $ksFile = dirname(self::resolveDatabasePath()) . '/../src/KonnektiveService.php';
                    if (!file_exists($ksFile)) $ksFile = __DIR__ . '/KonnektiveService.php';
                    if (file_exists($ksFile)) {
                        require_once $ksFile;
                        $crmService = new KonnektiveService();
                        $realReason = $crmService->getOrderDeclineReason($orderId);
                        if (!empty($realReason) && !in_array($realReason, ['Soft Decline', 'Hard Decline', 'Declined', 'DECLINED'], true)) {
                            $declineReason = $realReason;
                        }
                    }
                } catch (Throwable $e) {}
            }

            // Check campaign eligibility
            $campStmt = $db->prepare("SELECT * FROM campaigns WHERE campaign_id = :cid LIMIT 1");
            $campStmt->execute([':cid' => $campaignId]);
            $camp = $campStmt->fetch();
            $campaignName = !empty($camp['campaign_name']) ? $camp['campaign_name'] : ($options['campaign_name'] ?? null);

            $eligibility = 'ELIGIBLE';
            $eligibilityReason = 'Order captured from checkout';
            $retryStatus = 'pending';
            $nextAttempt = null;

            // Check pricing precedence (Tiers 2, 3, 4, 5)
            $plannedPrice = $amount;
            $pricingRuleSource = 'ORIGINAL_ORDER_AMOUNT';

            // Tier 2: Offer-specific campaign-product override
            $ocpStmt = $db->prepare("
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
            $ocpStmt->execute([':oid' => $offerId, ':cid' => $campaignId, ':pid' => $productId]);
            $ocpPrice = $ocpStmt->fetchColumn();

            if ($ocpPrice !== false && $ocpPrice !== null && (float)$ocpPrice > 0) {
                $plannedPrice = (float)$ocpPrice;
                $pricingRuleSource = 'OFFER_CAMPAIGN_PRODUCT_OVERRIDE';
            } else {
                // Tier 3: Shared campaign-product override
                $cpStmt = $db->prepare("
                    SELECT custom_retry_price FROM campaign_products 
                    WHERE campaign_id = :cid AND (product_id = :pid OR campaign_product_id = :pid) AND is_enabled = 1 
                    LIMIT 1
                ");
                $cpStmt->execute([':cid' => $campaignId, ':pid' => $productId]);
                $cpPrice = $cpStmt->fetchColumn();

                if ($cpPrice !== false && $cpPrice !== null && (float)$cpPrice > 0) {
                    $plannedPrice = (float)$cpPrice;
                    $pricingRuleSource = 'SHARED_CAMPAIGN_PRODUCT_OVERRIDE';
                } elseif ($camp && !empty($camp['retry_price_override']) && (float)$camp['retry_price_override'] > 0) {
                    // Tier 4: Campaign-level override
                    $plannedPrice = (float)$camp['retry_price_override'];
                    $pricingRuleSource = 'CAMPAIGN_OVERRIDE';
                }
            }

            // Extract marketing & tracking tokens across options, CRM lookup, POST, and SESSION
            $affId = !empty($options['aff_id']) ? trim((string)$options['aff_id']) : 
                (!empty($options['affId']) ? trim((string)$options['affId']) : 
                (!empty($crmLookupAffId) ? trim((string)$crmLookupAffId) : 
                (!empty($postData['affId']) ? trim((string)$postData['affId']) : 
                (!empty($postData['aff_id']) ? trim((string)$postData['aff_id']) : 
                (!empty($_SESSION['affid']) ? trim((string)$_SESSION['affid']) : 
                (!empty($_SESSION['aff_id']) ? trim((string)$_SESSION['aff_id']) : null))))));

            $sourceTitle = !empty($options['source_title']) ? trim((string)$options['source_title']) : 
                (!empty($options['sourceTitle']) ? trim((string)$options['sourceTitle']) : 
                (!empty($crmLookupSourceTitle) ? trim((string)$crmLookupSourceTitle) : 
                (!empty($postData['sourceTitle']) ? trim((string)$postData['sourceTitle']) : 
                (!empty($postData['source_title']) ? trim((string)$postData['source_title']) : 
                (!empty($_SESSION['source_title']) ? trim((string)$_SESSION['source_title']) : 
                (!empty($_SESSION['affiliate']) ? trim((string)$_SESSION['affiliate']) : null))))));

            $sourceValue1 = !empty($options['source_value1']) ? trim((string)$options['source_value1']) : 
                (!empty($postData['sourceValue1']) ? trim((string)$postData['sourceValue1']) : 
                (!empty($postData['source_value1']) ? trim((string)$postData['source_value1']) : 
                (!empty($_SESSION['c1']) ? trim((string)$_SESSION['c1']) : null)));

            $sourceValue2 = !empty($options['source_value2']) ? trim((string)$options['source_value2']) : 
                (!empty($postData['sourceValue2']) ? trim((string)$postData['sourceValue2']) : 
                (!empty($postData['source_value2']) ? trim((string)$postData['source_value2']) : 
                (!empty($_SESSION['c2']) ? trim((string)$_SESSION['c2']) : null)));

            $sourceValue3 = !empty($options['source_value3']) ? trim((string)$options['source_value3']) : 
                (!empty($postData['sourceValue3']) ? trim((string)$postData['sourceValue3']) : 
                (!empty($postData['source_value3']) ? trim((string)$postData['source_value3']) : 
                (!empty($_SESSION['c3']) ? trim((string)$_SESSION['c3']) : null)));

            // Check dynamic R_ affiliate mapping and recursive retry guard
            $affManagerFile = self::resolveAffiliateManagerPath();
            if ($affManagerFile) {
                require_once $affManagerFile;
            }

            $isRetryCreated = false;
            $affMappingStatus = 'unmapped';
            $retryAffId = null;
            $retryAffName = null;

            if (class_exists('AffiliateManager')) {
                $affMgr = new AffiliateManager();
                $isRetryCreated = $affMgr->isRetryAffiliate($sourceTitle ?? '') || $affMgr->isRetryAffiliate($affId ?? '') || ($sourceValue1 === 'retry_order');
                if ($isRetryCreated) {
                    $affMappingStatus = 'retry_affiliate';
                } elseif ($affId || $sourceTitle) {
                    $mapping = $affMgr->getMappingForAffiliate($affId ?: $sourceTitle);
                    $affMappingStatus = $mapping ? $mapping['status'] : 'unmapped';

                    $retryAffId = $mapping ? $mapping['retry_aff_id'] : null;
                    $retryAffName = $mapping ? $mapping['retry_title'] : null;
                    if (empty($sourceTitle) && !empty($mapping['original_title'])) {
                        $sourceTitle = $mapping['original_title'];
                    }
                }
            }

            if ($isError) {
                // Recursive Retry Prevention: Existing retry orders cannot enter retry workflow
                if ($isRetryCreated) {
                    $eligibility = 'BLOCKED_RETRY_CREATED_ORDER';
                    $eligibilityReason = 'Retry-created orders cannot enter the retry workflow.';
                    $retryStatus = 'paused';
                } elseif (empty($affId) && empty($sourceTitle)) {
                    $eligibility = 'BLOCKED_MISSING_AFFILIATE';
                    $eligibilityReason = 'Order lacks affiliate attribution for R_ mapping.';
                    $retryStatus = 'paused';
                } elseif ($affMappingStatus === 'unmapped') {
                    $eligibility = 'BLOCKED_MISSING_RETRY_AFFILIATE';
                    $eligibilityReason = "Missing verified R_ affiliate mapping for '" . ($sourceTitle ?: $affId) . "'.";
                    $retryStatus = 'paused';
                } elseif ($affMappingStatus === 'ambiguous') {
                    $eligibility = 'BLOCKED_AMBIGUOUS_RETRY_AFFILIATE';
                    $eligibilityReason = "Ambiguous R_ affiliate mapping for '" . ($sourceTitle ?: $affId) . "'. Requires administrative review.";
                    $retryStatus = 'paused';
                } elseif (count($allProdIds) > 1) {
                    // Requirement 3: Multi-product / upsell order safety
                    $eligibility = 'NEEDS_REVIEW_MULTI_PRODUCT';
                    $eligibilityReason = 'Order contains multiple products (' . implode(',', $allProdIds) . '). Flagged for review to avoid single-package pricing on complete order.';
                    $retryStatus = 'needs_review';
                } elseif (!$camp || (int)$camp['is_enabled'] !== 1) {
                    $eligibility = 'BLOCKED_CAMPAIGN';
                    $eligibilityReason = $camp ? "Campaign '{$camp['campaign_name']}' is disabled for reruns." : "Campaign {$campaignId} not configured.";
                    $retryStatus = 'paused';
                } elseif ($camp['campaign_type'] === 'PHONE') {
                    $eligibility = 'NEEDS_REVIEW_PHONE';
                    $eligibilityReason = 'Phone campaign requires manual review.';
                    $retryStatus = 'needs_review';
                } else {
                    // Check if decline reason is retryable
                    $isSoft = true;
                    $hardKeywords = ['stolen', 'lost', 'fraud', 'pickup', 'pick up', 'account closed', 'blacklisted', 'invalid card'];
                    foreach ($hardKeywords as $hKw) {
                        if (stripos($declineReason, $hKw) !== false) {
                            $isSoft = false;
                            break;
                        }
                    }

                    if ($isSoft) {
                        $delay = (int)($camp['retry_delay_minutes'] ?? 60);
                        $nextAttempt = date('Y-m-d H:i:s', strtotime("+{$delay} minutes"));
                        $retryStatus = 'scheduled';
                        $eligibilityReason = "Scheduled for retry in {$delay} minutes [R_ Affiliate: " . ($retryAffName ?: 'None') . "]";
                    } else {
                        $eligibility = 'BLOCKED_HARD_DECLINE';
                        $eligibilityReason = 'Terminal decline: ' . substr($declineReason, 0, 100);
                        $retryStatus = 'declined';
                    }
                }
            } else {
                $retryStatus = 'successful';
                $eligibility = 'COMPLETED';
                $eligibilityReason = 'Initial order approved.';
            }

            // Insert or update orders record
            $hasPaymentRef = !empty($cvvData) ? 1 : (!empty($paySourceId) ? 1 : 0);

            $sql = "
                INSERT INTO orders (
                    order_id, customer_id, pay_source_id, campaign_id, campaign_name, product_id, product_ids_json,
                    initial_product_id, initial_amount, transaction_count,
                    offer_id, offer_name, customer_name, customer_email, customer_phone,
                    address1, city, state, country, postal_code, ip_address,
                    original_amount, currency, transaction_status, decline_reason,
                    retry_eligibility, eligibility_reason, retry_status, next_attempt_at,
                    planned_price, pricing_rule_source, aff_id, source_title, source_value1, source_value2, source_value3,
                    is_retry_created_order, affiliate_mapping_status, retry_aff_id, retry_aff_name,
                    cvv_data, card_last4, card_expiry_date, card_type, has_payment_reference,
                    created_at, updated_at
                ) VALUES (
                    :oid, :cid, :psid, :camp, :camp_name, :prod, :pjson,
                    :init_prod, :init_amt, 1,
                    :offer_id, :offer_name, :cname, :cemail, :cphone,
                    :address1, :city, :state, :country, :postal_code, :ip_address,
                    :amt, :curr, :status, :reason,
                    :elig, :elig_rs, :r_status, :nxt,
                    :planned_prc, :rule_src, :aff, :stitle, :sv1, :sv2, :sv3,
                    :rco, :ams, :raid, :raname,
                    :cvv_data, :card_last4, :card_exp, :card_type, :has_pay_ref,
                    NOW(), NOW()
                ) ON DUPLICATE KEY UPDATE
                    customer_id = COALESCE(NULLIF(VALUES(customer_id), ''), customer_id),
                    pay_source_id = COALESCE(NULLIF(VALUES(pay_source_id), ''), pay_source_id),
                    campaign_name = COALESCE(NULLIF(VALUES(campaign_name), ''), campaign_name),
                    offer_id = COALESCE(NULLIF(VALUES(offer_id), ''), offer_id),
                    offer_name = COALESCE(NULLIF(VALUES(offer_name), ''), offer_name),
                    customer_name = COALESCE(NULLIF(VALUES(customer_name), ''), customer_name),
                    customer_email = COALESCE(NULLIF(VALUES(customer_email), ''), customer_email),
                    customer_phone = COALESCE(NULLIF(VALUES(customer_phone), ''), customer_phone),
                    address1 = COALESCE(NULLIF(VALUES(address1), ''), address1),
                    city = COALESCE(NULLIF(VALUES(city), ''), city),
                    state = COALESCE(NULLIF(VALUES(state), ''), state),
                    country = COALESCE(NULLIF(VALUES(country), ''), country),
                    postal_code = COALESCE(NULLIF(VALUES(postal_code), ''), postal_code),
                    ip_address = COALESCE(NULLIF(VALUES(ip_address), ''), ip_address),
                    initial_product_id = COALESCE(initial_product_id, NULLIF(product_id, 0), VALUES(product_id)),
                    initial_amount = COALESCE(initial_amount, NULLIF(original_amount, 0), VALUES(original_amount)),
                    product_id = VALUES(product_id),
                    product_ids_json = VALUES(product_ids_json),
                    original_amount = VALUES(original_amount),
                    cvv_data = COALESCE(VALUES(cvv_data), cvv_data),
                    card_last4 = COALESCE(VALUES(card_last4), card_last4),
                    card_expiry_date = COALESCE(VALUES(card_expiry_date), card_expiry_date),
                    card_type = COALESCE(VALUES(card_type), card_type),
                    has_payment_reference = IF(COALESCE(VALUES(cvv_data), cvv_data) IS NOT NULL, 1, has_payment_reference),
                    transaction_status = VALUES(transaction_status),
                    decline_reason = VALUES(decline_reason),
                    transaction_count = COALESCE(transaction_count, 1) + 1,
                    aff_id = COALESCE(VALUES(aff_id), aff_id),
                    source_title = COALESCE(VALUES(source_title), source_title),
                    source_value1 = COALESCE(VALUES(source_value1), source_value1),
                    source_value2 = COALESCE(VALUES(source_value2), source_value2),
                    source_value3 = COALESCE(VALUES(source_value3), source_value3),
                    is_retry_created_order = VALUES(is_retry_created_order),
                    affiliate_mapping_status = VALUES(affiliate_mapping_status),
                    retry_aff_id = COALESCE(VALUES(retry_aff_id), retry_aff_id),
                    retry_aff_name = COALESCE(VALUES(retry_aff_name), retry_aff_name),
                    retry_eligibility = IF(retry_status = 'successful', retry_eligibility, VALUES(retry_eligibility)),
                    eligibility_reason = IF(retry_status = 'successful', eligibility_reason, VALUES(eligibility_reason)),
                    retry_status = IF(retry_status = 'successful', retry_status, VALUES(retry_status)),
                    planned_price = IF(retry_status = 'successful', planned_price, VALUES(planned_price)),
                    pricing_rule_source = IF(retry_status = 'successful', pricing_rule_source, VALUES(pricing_rule_source)),
                    updated_at = NOW()
            ";

            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':oid' => $orderId,
                ':cid' => $customerId ?: null,
                ':psid' => $paySourceId ?: null,
                ':camp' => $campaignId,
                ':camp_name' => $campaignName,
                ':prod' => $productId,
                ':pjson' => $productIdsJson,
                ':init_prod' => $productId ?: null,
                ':init_amt' => $amount ?: null,
                ':offer_id' => $offerId,
                ':offer_name' => $offerName,
                ':cname' => $customerName,
                ':cemail' => $customerEmail,
                ':cphone' => $customerPhone,
                ':address1' => $address1,
                ':city' => $city,
                ':state' => $state,
                ':country' => $country,
                ':postal_code' => $postalCode,
                ':ip_address' => $ipAddress,
                ':amt' => $amount,
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
                ':cvv_data' => $cvvData,
                ':card_last4' => $cardLast4,
                ':card_exp' => $cardExp,
                ':card_type' => $cardType,
                ':has_pay_ref' => $hasPaymentRef,
                ':curr' => $currency,
                ':status' => $txStatus,
                ':reason' => $declineReason,
                ':elig' => $eligibility,
                ':elig_rs' => $eligibilityReason,
                ':r_status' => $retryStatus,
                ':nxt' => $nextAttempt,
            ]);
        } catch (Throwable $e) {
            // Fail-safe: Never disrupt customer checkout
            @error_log("RetryClient Notice: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Authenticated HTTPS Client for Remote Servers
     */
    private static function sendToRemoteApi(string $url, string $apiKey, array $postData, $konnektiveResponse, array $options): bool {
        try {
            $payload = json_encode([
                'post_data' => $postData,
                'crm_response' => $konnektiveResponse,
                'options' => $options
            ]);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 4,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey
                ]
            ]);

            $resp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (PHP_VERSION_ID < 80000) {
                curl_close($ch);
            }

            return $httpCode >= 200 && $httpCode < 300;
        } catch (Throwable $e) {
            @error_log("RetryClient Remote API Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reads $productSubFolder directly from the offer's app/configs/config.php file.
     * Guaranteed single source of truth without manual guessing.
     */
    public static function readProductSubFolderFromConfigFile(): ?string {
        $candidates = [
            dirname(__DIR__) . '/configs/config.php',
            dirname(__DIR__) . '/config/config.php',
            dirname(__DIR__, 2) . '/app/configs/config.php',
            dirname(__DIR__, 2) . '/configs/config.php',
            dirname(__DIR__, 2) . '/config/config.php',
            dirname(__DIR__, 2) . '/config.php',
            dirname(__DIR__, 3) . '/app/configs/config.php',
            dirname(__DIR__, 3) . '/configs/config.php',
        ];

        if (!empty($_SERVER['SCRIPT_FILENAME'])) {
            $scriptDir = dirname($_SERVER['SCRIPT_FILENAME']);
            $candidates[] = $scriptDir . '/../configs/config.php';
            $candidates[] = $scriptDir . '/../config/config.php';
            $candidates[] = dirname($scriptDir) . '/configs/config.php';
            $candidates[] = dirname($scriptDir) . '/app/configs/config.php';
        }

        if (function_exists('get_included_files')) {
            foreach ((array)get_included_files() as $file) {
                if (preg_match('#/(?:app/)?configs?/config\.php$#i', $file)) {
                    $candidates[] = $file;
                }
            }
        }

        foreach ($candidates as $filePath) {
            if ($filePath && file_exists($filePath)) {
                $content = @file_get_contents($filePath);
                if ($content && preg_match('/\$productSubFolder\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $m)) {
                    $found = trim($m[1], '/');
                    if ($found !== '') {
                        return $found;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Reads $offerName directly from the offer's app/configs/config.php file.
     */
    public static function readOfferNameFromConfigFile(): ?string {
        $candidates = [
            dirname(__DIR__) . '/configs/config.php',
            dirname(__DIR__) . '/config/config.php',
            dirname(__DIR__, 2) . '/app/configs/config.php',
            dirname(__DIR__, 2) . '/configs/config.php',
            dirname(__DIR__, 2) . '/config/config.php',
            dirname(__DIR__, 2) . '/config.php',
        ];

        if (!empty($_SERVER['SCRIPT_FILENAME'])) {
            $scriptDir = dirname($_SERVER['SCRIPT_FILENAME']);
            $candidates[] = $scriptDir . '/../configs/config.php';
            $candidates[] = dirname($scriptDir) . '/configs/config.php';
        }

        foreach ($candidates as $filePath) {
            if ($filePath && file_exists($filePath)) {
                $content = @file_get_contents($filePath);
                if ($content && preg_match('/\$offerName\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $m)) {
                    $found = trim($m[1]);
                    if ($found !== '') {
                        return $found;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Accurately detects offer identifier across top-level funnels, subpaths, and query strings.
     * Automatically reads $productSubFolder from config.php as canonical source of truth.
     * Handles formats like is-dtc-aa, is-dtc-aa/MCv1, etc.
     */
    public static function detectOffer(array $options = [], array $postData = []): array {
        global $configs, $config;
        $cfg = $configs ?? ($config ?? null);

        $offerId = trim((string)($options['offer_id'] ?? ''));
        $offerName = trim((string)($options['offer_name'] ?? ''));

        // 0. Primary Source of Truth: Read directly from offer's config file or scope ($productSubFolder)
        if (empty($offerId)) {
            if (!empty($GLOBALS['productSubFolder'])) {
                $offerId = trim((string)$GLOBALS['productSubFolder'], '/');
            }
            if (empty($offerId)) {
                $cfgOffer = self::readProductSubFolderFromConfigFile();
                if ($cfgOffer) {
                    $offerId = $cfgOffer;
                }
            }
        }

        if (empty($offerName)) {
            if (!empty($GLOBALS['offerName'])) {
                $offerName = trim((string)$GLOBALS['offerName']);
            }
            if (empty($offerName)) {
                $cfgName = self::readOfferNameFromConfigFile();
                if ($cfgName) {
                    $offerName = $cfgName;
                }
            }
        }

        // 1. Check configs if available
        if (empty($offerId)) {
            if (isset($cfg) && !empty($cfg->offer_id)) {
                $offerId = trim((string)$cfg->offer_id, '/');
            } elseif (isset($cfg) && !empty($cfg->product_sub_folder)) {
                $offerId = trim((string)$cfg->product_sub_folder, '/');
            } elseif (isset($cfg) && !empty($cfg->productSubFolder)) {
                $offerId = trim((string)$cfg->productSubFolder, '/');
            }
        }

        // 2. Check POST data
        if (empty($offerId)) {
            $rawPost = self::firstNonEmpty($postData, ['offer_id', 'product_sub_folder', 'productSubFolder', 'offerId', 'product_folder']);
            if ($rawPost) {
                $offerId = trim($rawPost, '/');
            }
        }

        // 3. Inspect HTTP_REFERER and REQUEST_URI (handles sub-funnels e.g. is-dtc-aa/MCv1)
        if (empty($offerId)) {
            $urlCandidates = [
                $_SERVER['HTTP_REFERER'] ?? '',
                $_SERVER['REQUEST_URI'] ?? '',
                $_SERVER['SCRIPT_NAME'] ?? '',
                $_SERVER['PHP_SELF'] ?? ''
            ];

            foreach ($urlCandidates as $u) {
                if (empty($u)) continue;
                $path = parse_url($u, PHP_URL_PATH);
                if (empty($path)) continue;

                // Match pattern /is-[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)?/
                if (preg_match('#/(is-[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)?)(?:/|\?|$)#i', $path, $m)) {
                    $matched = trim($m[1], '/');
                    // Avoid matches inside app/src or system folders
                    if (!preg_match('#/(app|src|retry_system|KK-Rerun)$#i', $matched)) {
                        $offerId = $matched;
                        break;
                    }
                }
            }
        }

        // 4. Inspect filesystem hierarchy
        if (empty($offerId)) {
            $dirParts = explode(DIRECTORY_SEPARATOR, trim(__DIR__, DIRECTORY_SEPARATOR));
            // Find segment starting with is-
            for ($i = count($dirParts) - 1; $i >= 0; $i--) {
                $seg = $dirParts[$i];
                if (strpos($seg, 'is-') === 0) {
                    // Check if there is a subvariant following it (e.g. is-dtc-aa/MCv1)
                    if (isset($dirParts[$i + 1]) && !in_array($dirParts[$i + 1], ['app', 'src', 'config', 'configs'], true)) {
                        $offerId = $seg . '/' . $dirParts[$i + 1];
                    } else {
                        $offerId = $seg;
                    }
                    break;
                }
            }
        }

        // 5. Fallback folder detection
        if (empty($offerId)) {
            $parent2 = basename(dirname(__DIR__, 2)); // e.g. offer folder
            if (!empty($parent2) && !in_array($parent2, ['src', 'app', 'retry_system', 'KK-Rerun', 'html', 'www', 'public_html'], true)) {
                $offerId = $parent2;
            }
        }

        if (empty($offerName)) {
            if (isset($cfg) && !empty($cfg->offer_name)) {
                $offerName = (string)$cfg->offer_name;
            } elseif (!empty($offerId)) {
                $offerName = ucwords(str_replace(['-', '_', '/'], ' ', $offerId));
            } else {
                $offerName = 'Direct';
            }
        }

        return ['offer_id' => $offerId ?: null, 'offer_name' => $offerName];
    }

    /**
     * Resolves the absolute path to database.php across all deployment topologies
     */
    public static function resolveDatabasePath(): ?string {
        $candidates = [
            __DIR__ . '/../config/database.php',
            __DIR__ . '/config/database.php',
            dirname(__DIR__, 2) . '/KK-Rerun/config/database.php',
            dirname(__DIR__, 3) . '/KK-Rerun/config/database.php',
            dirname(__DIR__, 4) . '/KK-Rerun/config/database.php',
            dirname(__DIR__, 5) . '/KK-Rerun/config/database.php',
            dirname(__DIR__, 6) . '/KK-Rerun/config/database.php',
            dirname(__DIR__, 2) . '/retry_system/config/database.php',
            dirname(__DIR__, 3) . '/retry_system/config/database.php',
            dirname(__DIR__, 4) . '/retry_system/config/database.php',
            dirname(__DIR__, 5) . '/retry_system/config/database.php',
            dirname(__DIR__, 6) . '/retry_system/config/database.php',
            (!empty($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/KK-Rerun/config/database.php' : null),
            (!empty($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/retry_system/config/database.php' : null),
            '/var/www/html_domains/getironstamina.com/public_html/KK-Rerun/config/database.php',
            '/var/www/html_domains/getironstamina.com/public_html/retry_system/config/database.php',
            '/var/www/html/KK-Rerun/config/database.php',
            '/var/www/html/retry_system/config/database.php',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate && file_exists($candidate)) {
                return $candidate;
            }
        }
        @error_log("[RetryClient] Database configuration not found across candidates from: " . __DIR__);
        return null;
    }

    /**
     * Resolves the absolute path to AffiliateManager.php across all deployment topologies
     */
    public static function resolveAffiliateManagerPath(): ?string {
        $candidates = [
            __DIR__ . '/AffiliateManager.php',
            dirname(__DIR__, 2) . '/KK-Rerun/src/AffiliateManager.php',
            dirname(__DIR__, 3) . '/KK-Rerun/src/AffiliateManager.php',
            dirname(__DIR__, 4) . '/KK-Rerun/src/AffiliateManager.php',
            dirname(__DIR__, 5) . '/KK-Rerun/src/AffiliateManager.php',
            dirname(__DIR__, 6) . '/KK-Rerun/src/AffiliateManager.php',
            dirname(__DIR__, 2) . '/retry_system/src/AffiliateManager.php',
            dirname(__DIR__, 3) . '/retry_system/src/AffiliateManager.php',
            dirname(__DIR__, 4) . '/retry_system/src/AffiliateManager.php',
            dirname(__DIR__, 5) . '/retry_system/src/AffiliateManager.php',
            dirname(__DIR__, 6) . '/retry_system/src/AffiliateManager.php',
            (!empty($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/KK-Rerun/src/AffiliateManager.php' : null),
            (!empty($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/retry_system/src/AffiliateManager.php' : null),
            '/var/www/html_domains/getironstamina.com/public_html/KK-Rerun/src/AffiliateManager.php',
            '/var/www/html_domains/getironstamina.com/public_html/retry_system/src/AffiliateManager.php',
            '/var/www/html/KK-Rerun/src/AffiliateManager.php',
            '/var/www/html/retry_system/src/AffiliateManager.php',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate && file_exists($candidate)) {
                return $candidate;
            }
        }
        return null;
    }
}
}

