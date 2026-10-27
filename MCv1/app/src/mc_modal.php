<?php
/**
 * Mastercard +1 Free Bottle Upgrade Modal Partial (S Offer Popup Design)
 * Modular, customizable, and reads directly from $configs->mc_promo.
 */
$mcPromo = (isset($configs) && isset($configs->mc_promo)) ? $configs->mc_promo : (object) array();

// Extract per-package configurations
$packagesConfig = array();
if (!empty($mcPromo->packages)) {
    foreach ($mcPromo->packages as $pkgKey => $pkgVal) {
        $packagesConfig[$pkgKey] = (array) $pkgVal;
    }
}

// Current active package (default to promo_package, e.g. 5 or 6)
$promoPackage   = $mcPromo->promo_package ?? 5;
$activePackage  = $_SESSION['package'] ?? $_REQUEST['pk'] ?? $promoPackage;
$currentPkgData = $packagesConfig[$activePackage] ?? ($packagesConfig[$promoPackage] ?? (reset($packagesConfig) ?: array()));

$topTitle      = $currentPkgData['top_title'] ?? $mcPromo->modal_top_title ?? 'SPECIAL MASTERCARD UPGRADE <br>1 FREE BOTTLE!';
$headline      = $currentPkgData['headline'] ?? $mcPromo->modal_headline ?? 'Upgrade your Package to <strong>5-Bottle Package & get 1 FREE Bottle</strong> when you pay with Mastercard.';
$subtext       = $currentPkgData['subtext'] ?? '';
$logoImg       = $mcPromo->modal_logo_img ?? 'assets/images/mastercard.png';
$btnYesText    = $currentPkgData['btn_yes'] ?? $mcPromo->modal_btn_yes ?? '✓ Yes, I want to receive a FREE Bottle with my MasterCard';
$btnNoText     = $currentPkgData['btn_no'] ?? $mcPromo->modal_btn_no ?? 'No Thanks, continue with my Visa card';
$securityNote  = $mcPromo->modal_security_text ?? '🔒 Safe &amp; Secure 256-Bit Encrypted Checkout';

$rootPath = (isset($configs) && isset($configs->root_path)) ? $configs->root_path : '';
$resolvedModalLogo = $logoImg;
if (!empty($logoImg) && strpos($logoImg, 'http') !== 0 && strpos($logoImg, '/') !== 0 && !empty($rootPath)) {
    $resolvedModalLogo = rtrim($rootPath, '/') . '/' . ltrim($logoImg, '/');
}
?>
<!-- Visa Offer Modal: Mastercard +1 Free Bottle Upgrade (S Offer Style) -->
<div class="mc-modal-backdrop" id="mcVisaOfferModal" style="display: none;">
    <div class="mc-modal-box s-offer-modal">
        <button type="button" class="mc-modal-x" id="mcModalXBtn" aria-label="Close">&times;</button>
        <div class="mc-modal-inner">
            <!-- Top Title (HTML Supported) -->
            <h3 class="mc-modal-top-title" id="mcModalTopTitle"><?= $topTitle; ?></h3>
            <hr class="mc-modal-divider">

            <!-- Bold Headline (HTML Supported) -->
            <p class="mc-modal-bold-headline" id="mcModalHeadline">
                <?= $headline; ?>
            </p>

            <!-- Subtext / Details for package upgrade (HTML Supported) -->
            <p class="mc-modal-subtext" id="mcModalSubText" <?= empty($subtext) ? 'style="display:none;"' : ''; ?>>
                <?= $subtext; ?>
            </p>

            <!-- Mastercard Logo -->
            <div class="mc-modal-logo-area">
                <img src="<?= htmlspecialchars($resolvedModalLogo); ?>" alt="Mastercard" class="mc-modal-logo-img">
            </div>

            <!-- Action Buttons (HTML Supported) -->
            <div class="mc-modal-actions-container">
                <button type="button" class="btn-mc-confirm-yes" id="btnMcYes">
                    <span id="mcModalBtnYesText"><?= $btnYesText; ?></span>
                </button>
                <button type="button" class="btn-mc-decline-no" id="btnMcNo">
                    <span id="mcModalBtnNoText"><?= $btnNoText; ?></span>
                </button>
            </div>

            <div class="mc-modal-security-note">
                <?= $securityNote; ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
window.MCPromoConfig = window.MCPromoConfig || {};
window.MCPromoConfig.promoPackage = <?= json_encode($mcPromo->promo_package ?? 5); ?>;
window.MCPromoConfig.promoPrice = <?= json_encode((string)($mcPromo->promo_price ?? '198.80')); ?>;
window.MCPromoConfig.packages = <?= json_encode($packagesConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
window.MCPromoConfig.testCards = <?= json_encode($mcPromo->test_cards ?? (object) array(
    'generic' => array('0000', '0000000000000000'),
    'visa'    => array('7411111111111111'),
    'master'  => array('7555555555555444'),
)); ?>;
</script>
