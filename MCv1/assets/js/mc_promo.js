/**
 * ==========================================================================
 * Mastercard Promo & Free Bottle Upgrade Module
 * Handles card detection, Visa modal offer, price updates & synchronization.
 * Plug & play across any checkout lander.
 * ==========================================================================
 */

(function($) {
    'use strict';

    // Configurable options with sensible defaults (supports runtime window.MCPromoConfig)
    var userCfg = window.MCPromoConfig || {};
    var cfg = {
        modalId: userCfg.modalId || '#mcVisaOfferModal',
        cardInputId: userCfg.cardInputId || '#cardNumber',
        cardSelectId: userCfg.cardSelectId || '#card_type_select',
        bannerId: userCfg.bannerId || '#mastercardCheckoutBanner',
        detectEndpoint: userCfg.detectEndpoint || (window.location.pathname.indexOf('/mobile/') !== -1 ? '../app/src/detect_card.php' : 'app/src/detect_card.php'),
        bannerPill: userCfg.bannerPill || 'LIMITED OFFER:',
        bannerHeadline: userCfg.bannerHeadline || 'Receive an Additional bottle for FREE when paying with Mastercard!',
        bannerAppliedText: userCfg.bannerAppliedText || '+1 FREE BOTTLE ADDED WITH MASTERCARD!',
        promoPackage: userCfg.promoPackage || 5,
        promoPrice: userCfg.promoPrice || '198.80',
        packages: userCfg.packages || {},
        p1: $.extend({
            headerTitle: '#p1-header-title',
            bottle6: '#product1-image6',
            productBox: '.product1',
            packTitle: '#p1-pack-title',
            perPrice: '#p1-per-price',
            savePrice: '#p1-save-price',
            retailPrice: '#p1-retail-price',
            textNormal: 'BUY 3 GET 2 FREE',
            textPromo: 'BUY 3 GET 3 FREE (+1 BONUS)',
            packNormal: '5 Bottle Performance Pack',
            packPromo: '6 Bottle Performance Pack <em class="mc-pack-sub">(Includes +1 FREE Bottle!)</em>',
            perNormal: '39.76',
            perPromo: '33.13',
            saveNormal: '151.15',
            savePromo: '221.14',
            retailNormal: '349.95',
            retailPromo: '419.94'
        }, userCfg.p1 || {})
    };

    var currentCardType = '';
    var visaPopupShown = false;
    var ajaxDetectTimer = null;

    function attachFreeBottle() {
        // Promo applies to Plan 1 (5 Bottle Pack upgraded to 6 Bottles)
        $(cfg.p1.headerTitle).text(cfg.p1.textPromo);
        $(cfg.p1.productBox).addClass('mc-6b-active');
        $(cfg.p1.bottle6).show();
        $(cfg.p1.packTitle).html(cfg.p1.packPromo);

        // Update pricing displays if present
        if ($(cfg.p1.perPrice).length) {
            $(cfg.p1.perPrice).text(cfg.p1.perPromo);
        }
        if ($(cfg.p1.savePrice).length) {
            $(cfg.p1.savePrice).text(cfg.p1.savePromo);
        }
        if ($(cfg.p1.retailPrice).length) {
            $(cfg.p1.retailPrice).text(cfg.p1.retailPromo);
        }

        // Single dark green banner update
        $(cfg.bannerId).addClass('promo-applied');
        $('#mcBannerDefault').hide();
        $('#mcBannerApplied').show();
        if ($('#mcBannerAppliedText').length && cfg.bannerAppliedText) {
            $('#mcBannerAppliedText').text(cfg.bannerAppliedText);
        }

        // Notify server session
        $.ajax({
            type: 'POST',
            url: cfg.detectEndpoint,
            dataType: 'json',
            data: { action: 'set_mastercard_promo' }
        });
    }

    function detachFreeBottle() {
        // Revert Plan 1
        $(cfg.p1.headerTitle).text(cfg.p1.textNormal);
        $(cfg.p1.productBox).removeClass('mc-6b-active');
        $(cfg.p1.bottle6).hide();
        $(cfg.p1.packTitle).text(cfg.p1.packNormal);

        // Revert pricing displays
        if ($(cfg.p1.perPrice).length) {
            $(cfg.p1.perPrice).text(cfg.p1.perNormal);
        }
        if ($(cfg.p1.savePrice).length) {
            $(cfg.p1.savePrice).text(cfg.p1.saveNormal);
        }
        if ($(cfg.p1.retailPrice).length) {
            $(cfg.p1.retailPrice).text(cfg.p1.retailNormal);
        }

        // Revert Banner (Back to yellow box)
        $(cfg.bannerId).removeClass('promo-applied');
        $('#mcBannerApplied').hide();
        $('#mcBannerDefault').show();

        // Notify server session
        $.ajax({
            type: 'POST',
            url: cfg.detectEndpoint,
            dataType: 'json',
            data: { action: 'cancel_promo' }
        });
    }

    function getPromoPkg() {
        return parseInt(cfg.promoPackage || (window.MCPromoConfig && window.MCPromoConfig.promoPackage) || 5);
    }

    function getActivePkg() {
        var promoPkg = getPromoPkg();
        var val = $('input[name=package]').val() || $('#package').val();
        return val ? parseInt(val) : promoPkg;
    }

    function updatePromoState() {
        var isMobile = (window.location.pathname.indexOf('/mobile/') !== -1);
        var promoPkg = getPromoPkg();
        var activePkg = getActivePkg();

        if (isMobile) {
            if (currentCardType === 'master' && activePkg === promoPkg) {
                attachFreeBottle();
            } else {
                detachFreeBottle();
            }
        } else {
            if (currentCardType === 'master') {
                attachFreeBottle();
            } else {
                detachFreeBottle();
            }
        }
    }

    function setCardType(type, fromUserDropdown) {
        currentCardType = type;
        if (!fromUserDropdown) {
            $(cfg.cardSelectId).val(type);
        }
        $('input[name=fields_cc_type]').val(type);
        $('#cc_type').val(type);

        updatePromoState();
    }

    function renderModalContent(pkg) {
        var promoPkg = getPromoPkg();
        var activePkg = parseInt(pkg !== undefined ? pkg : getActivePkg());
        var packagesCfg = (window.MCPromoConfig && window.MCPromoConfig.packages) ? window.MCPromoConfig.packages : (cfg.packages || {});
        var pkgData = packagesCfg[activePkg] || null;

        var topTitle = (pkgData && pkgData.top_title) ? pkgData.top_title : 'SPECIAL MASTERCARD UPGRADE <br>1 FREE BOTTLE!';
        var headline = (pkgData && pkgData.headline) ? pkgData.headline : 'Upgrade your Package to <strong>5-Bottle Package & get 1 FREE Bottle</strong> when you pay with Mastercard.';
        var subtext = (pkgData && pkgData.subtext !== undefined) ? pkgData.subtext : '';
        var btnYes = (pkgData && pkgData.btn_yes) ? pkgData.btn_yes : '✓ Yes, I want to receive a FREE Bottle with my MasterCard';
        var btnNo = (pkgData && pkgData.btn_no) ? pkgData.btn_no : 'No Thanks, continue with my Visa card';

        // Fallback defaults if no packages config provided for this package
        if (!pkgData) {
            if (activePkg === promoPkg) {
                topTitle = 'SPECIAL MASTERCARD UPGRADE <br>1 FREE BOTTLE!';
                headline = 'Get 6 Bottles for Only <span style="color:#16a34a; font-weight:800;">$33.13 / bottle</span>';
                subtext = 'Pay with Mastercard to get <strong>+1 EXTRA FREE Bottle</strong> added to your 5-Bottle package (Total 6 Bottles) at no extra charge!';
                btnYes = '✓ Yes, Add +1 FREE Bottle with my Mastercard';
                btnNo = 'No Thanks, continue with my Visa card';
            } else {
                topTitle = 'SPECIAL MASTERCARD UPGRADE <br>1 FREE BOTTLE!';
                headline = 'Get 6 Bottles for Only <span style="color:#16a34a; font-weight:800;">$33.13 / bottle</span>';
                subtext = 'Upgrade your Package to <strong>5-Bottle Package & get 1 FREE Bottle</strong> when you pay with Mastercard.';
                btnYes = '✓ Yes, Upgrade to 6 Bottles with Mastercard';
                btnNo = 'No Thanks, keep my Package with Visa';
            }
        }

        if ($('#mcModalTopTitle').length) {
            $('#mcModalTopTitle').html(topTitle);
        }
        if ($('#mcModalHeadline').length) {
            $('#mcModalHeadline').html(headline);
        }
        if ($('#mcModalSubText').length) {
            if (subtext) {
                $('#mcModalSubText').html(subtext).show();
            } else {
                $('#mcModalSubText').hide();
            }
        }
        if ($('#mcModalBtnYesText').length) {
            $('#mcModalBtnYesText').html(btnYes);
        }
        if ($('#mcModalBtnNoText').length) {
            $('#mcModalBtnNoText').html(btnNo);
        }
    }

    function openVisaOfferModal(pkg) {
        if (visaPopupShown) return;
        visaPopupShown = true;
        renderModalContent(pkg);
        $(cfg.modalId).fadeIn(200);
    }

    // Dropdown change listener
    $(document).on('change', cfg.cardSelectId, function() {
        var val = $(this).val();

        // Clear card input field whenever user manually changes card type to ensure consistency
        $(cfg.cardInputId).val('').focus();

        if (val === 'visa') {
            setCardType('visa', true);
            var activePkg = getActivePkg();
            openVisaOfferModal(activePkg);
        } else if (val === 'master') {
            setCardType('master', true);
        } else {
            setCardType('', true);
        }
    });

    // Package change listener - trigger Visa upgrade popup if user switches packages while Visa is selected
    $(document).on('click', '.product-selection > .product, [data-pkg]', function() {
        setTimeout(function() {
            var activePkg = getActivePkg();
            if (currentCardType === 'visa' && !visaPopupShown) {
                openVisaOfferModal(activePkg);
            }
        }, 15);
    });

    // Modal Action: User accepts Mastercard offer
    $(document).on('click', '#btnMcYes', function() {
        $(cfg.modalId).fadeOut(200);

        var promoPkg = getPromoPkg();
        var promoPrice = (cfg.promoPrice || (window.MCPromoConfig && window.MCPromoConfig.promoPrice) || '198.80');
        var activePkg = getActivePkg();

        // If user was on a lower tier, automatically upgrade their package to promoPkg
        if (activePkg !== promoPkg) {
            if ($('[data-pkg="' + promoPkg + '"]').length) {
                $('[data-pkg="' + promoPkg + '"]').trigger('click');
            } else if ($('.product1').length) {
                // Desktop: click package 1 (.product1) to update UI & active states
                $('.product1').trigger('click');
            }
            // Ensure hidden inputs across mobile and desktop are updated
            $('input[name=package]').val(promoPkg);
            $('#package').val(promoPkg);
            if ($('#amount').length) {
                $('#amount').val(promoPrice);
            }
        }

        setCardType('master', false);
        attachFreeBottle();
        $(cfg.cardInputId).val('').focus();
    });

    // Modal Action: User declines Mastercard offer
    function dismissVisaModal() {
        $(cfg.modalId).fadeOut(200);
        setCardType('visa', false);
        $(cfg.cardInputId).focus();
    }

    $(document).on('click', '#btnMcNo, #mcModalXBtn', dismissVisaModal);

    $(document).on('click', cfg.modalId, function(e) {
        if ($(e.target).is(cfg.modalId)) {
            dismissVisaModal();
        }
    });

    // Card number input monitoring & automatic detection
    function handleCardNumberChange() {
        var rawVal = $(cfg.cardInputId).val() || '';
        var cleanNum = rawVal.replace(/\D/g, '');

        if (cleanNum.length === 0) {
            var selectedType = $(cfg.cardSelectId).val() || '';
            if (selectedType !== 'master') {
                setCardType(selectedType, false);
            }
            return;
        }

        var activePkg = getActivePkg();

        // Check configurable test card arrays
        var tc = (window.MCPromoConfig && window.MCPromoConfig.testCards) ? window.MCPromoConfig.testCards : (cfg.testCards || {});
        var genericList = (tc.generic && Array.isArray(tc.generic)) ? tc.generic : ['0000'];
        var visaList = (tc.visa && Array.isArray(tc.visa)) ? tc.visa : ['7411111111111111'];
        var masterList = (tc.master && Array.isArray(tc.master)) ? tc.master : ['7555555555555444'];

        function isCardMatch(num, list) {
            if (!num || !list || !list.length) return false;
            for (var i = 0; i < list.length; i++) {
                var item = String(list[i]).replace(/\D/g, '');
                if (item && (num === item || num.indexOf(item) === 0)) {
                    return true;
                }
            }
            return false;
        }

        // 1. Whitelisted Generic Test Card (e.g. 0000...): follows manual dropdown selection
        if (isCardMatch(cleanNum, genericList)) {
            var selectedType = $(cfg.cardSelectId).val() || '';
            setCardType(selectedType, false);
            return;
        }

        // 2. Whitelisted Visa Test Card: acts as Visa
        if (isCardMatch(cleanNum, visaList)) {
            setCardType('visa', false);
            openVisaOfferModal(activePkg);
            return;
        }

        // 3. Whitelisted Mastercard Test Card: acts as Mastercard
        if (isCardMatch(cleanNum, masterList)) {
            setCardType('master', false);
            return;
        }

        // Support Konnektive test cards prefixed with '7' (e.g. 74... for Visa, 75... for Mastercard)
        var testNum = cleanNum;
        if (cleanNum.charAt(0) === '7' && cleanNum.length > 1) {
            testNum = cleanNum.substring(1);
        }

        var firstChar = testNum.charAt(0);

        // 4. Visa: starts with 4 (or Konnektive test card 74...)
        if (firstChar === '4') {
            setCardType('visa', false);
            openVisaOfferModal(activePkg);
            return;
        }

        // 5. Mastercard: starts with 5 or 2 (or Konnektive test card 75... / 72...)
        if (firstChar === '5' || firstChar === '2') {
            setCardType('master', false);
            return;
        }

        // 6. Amex (starts with 3) or Discover (starts with 6) -> immediately revoke promo
        if (firstChar === '3' || firstChar === '6') {
            setCardType(firstChar === '3' ? 'amex' : 'discover', false);
            return;
        }

        // 7. Generic test card (0000...): follows the dropdown
        if (cleanNum.indexOf('0000') === 0) {
            var selectedType = $(cfg.cardSelectId).val() || '';
            setCardType(selectedType, false);
            return;
        }

        // 8. Fallback Ajax detection
        clearTimeout(ajaxDetectTimer);
        ajaxDetectTimer = setTimeout(function() {
            $.ajax({
                type: 'POST',
                url: cfg.detectEndpoint,
                dataType: 'json',
                data: { action: 'detect', card_number: cleanNum },
                success: function(resp) {
                    if (!resp || resp.status !== 'success') return;
                    if (resp.is_mastercard) {
                        setCardType('master', false);
                    } else if (resp.is_visa) {
                        setCardType('visa', false);
                        var pkgNow = getActivePkg();
                        openVisaOfferModal(pkgNow);
                    } else {
                        setCardType(resp.card_type || '', false);
                    }
                }
            });
        }, 120);
    }

    $(document).on('input keyup paste change', cfg.cardInputId, handleCardNumberChange);

    // Auto-initialize on DOM ready if card type is already selected (e.g. page refresh / back navigation)
    $(function() {
        var initialType = $(cfg.cardSelectId).val();
        if (initialType) {
            setCardType(initialType, true);
        }
    });

    // Expose helpers globally
    window.setCardType = setCardType;
    window.MCPromo = {
        setCardType: setCardType,
        attachFreeBottle: attachFreeBottle,
        detachFreeBottle: detachFreeBottle,
        dismissVisaModal: dismissVisaModal
    };

})(jQuery);
