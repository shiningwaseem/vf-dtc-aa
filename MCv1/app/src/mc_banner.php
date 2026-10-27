<?php
/**
 * Mastercard Checkout Promo Banner Partial (S Offer Style)
 * Modular, customizable, and easy to drop into any checkout lander right above the payment form.
 */
$mcPromo = (isset($configs) && isset($configs->mc_promo)) ? $configs->mc_promo : (object) array();

$bannerLogo     = $mcPromo->banner_logo_img ?? 'assets/images/mastercard.png';
$bannerPill     = $mcPromo->banner_pill ?? 'LIMITED OFFER:';
$bannerHeadline = $mcPromo->banner_headline ?? 'Receive an Additional bottle for FREE when paying with Mastercard!';
$bannerApplied  = $mcPromo->banner_applied_text ?? '🎉 LIMITED OFFER APPLIED: +1 Free Bottle Added with Mastercard!';

$rootPath = (isset($configs) && isset($configs->root_path)) ? $configs->root_path : '';
$resolvedLogo = $bannerLogo;
if (!empty($bannerLogo) && strpos($bannerLogo, 'http') !== 0 && strpos($bannerLogo, '/') !== 0 && !empty($rootPath)) {
    $resolvedLogo = rtrim($rootPath, '/') . '/' . ltrim($bannerLogo, '/');
}
?>
<!-- Mastercard Special Offer Banner -->
<div class="mastercard-checkout-banner yellow-offer-box" id="mastercardCheckoutBanner">
    <div class="mc-banner-default-view" id="mcBannerDefault">
        <?php if (!empty($resolvedLogo)): ?>
            <div class="mc-banner-logo-wrap">
                <img src="<?= htmlspecialchars($resolvedLogo); ?>" alt="Mastercard" class="mc-banner-logo-img">
            </div>
        <?php endif; ?>
        <span class="mc-banner-pill-red" id="mcBannerPill"><?= htmlspecialchars($bannerPill); ?></span>
        <span class="mc-banner-heading-text" id="mcBannerHeading"><?= htmlspecialchars($bannerHeadline); ?></span>
    </div>
    <div class="mc-banner-applied-view" id="mcBannerApplied" style="display: none;">
        <span class="mc-banner-applied-text" id="mcBannerAppliedText"><?= htmlspecialchars($bannerApplied); ?></span>
    </div>
</div>
<script>
window.MCPromoConfig = window.MCPromoConfig || {};
window.MCPromoConfig.bannerLogo = <?= json_encode($resolvedLogo); ?>;
window.MCPromoConfig.bannerPill = <?= json_encode($bannerPill); ?>;
window.MCPromoConfig.bannerHeadline = <?= json_encode($bannerHeadline); ?>;
window.MCPromoConfig.bannerAppliedText = <?= json_encode($bannerApplied); ?>;
</script>
