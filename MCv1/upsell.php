<?php
include("_global.php");

$decline_link = $configs->path . "confirmation.php?customerId=" . $_GET["customerId"] . "&orderId=" . $_GET["orderId"] . "&declined=1";

if (isset($_SESSION["added_upsell"]) && $_SESSION["added_upsell"] == true) {
    header("Location: " . $configs->path . "confirmation.php");
}

$device_type = 'desktop';
if ($mobile_detect->isMobile() && !$mobile_detect->isTablet()) {
    $device_type = 'mobile';
}

// Exit-intent discount popup (only when a discounted product is configured for this affiliate)
$discount = $configs->upsell_discount;
$discount_available = (bool) get_discount_upsell_id($configs);
$_SESSION["upsell_discount_offered"] = $discount_available;
$discount_rebill = $discount->rebill_discounted ? $discount->rebill_price_discounted : $discount->rebill_price_regular;
?>
<!DOCTYPE html>
<!-- saved from url=(0059)https://getprimotrt.com/trtv2/upsell.php?prospectId=6281187 -->
<html lang="en">


<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">


    <title><?php echo $configs->step2_name; ?></title>
    <meta name="description" content="<?php echo $configs->step1_name; ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <meta http-equiv="content-language" content="en-us">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="HandheldFriendly" content="true">
    <meta name="viewport" content="width=device-width,user-scalable=no">
    <link href="<?php echo $configs->root_path ?>/assets/css/app.css" rel="stylesheet">
    <link href="<?php echo $configs->root_path ?>/assets/brand/favicon.png" rel="icon" type="image/png">

    <link href="assets/images/favicon.png" rel="icon" type="image/png">

    <!-- Required meta tags -->

    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <!-- google fonts -->
    <!-- <link href="<?php echo $configs->root_path ?>/assets/css/css" rel="stylesheet"> -->
    <link href="https://fonts.googleapis.com/css?family=Lato:100,300,400,700,900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Khula:300,400,600,700,800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo $configs->root_path ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $configs->root_path ?>/assets/css/upsell/custom.css">
    <link rel="stylesheet" href="<?php echo $configs->root_path ?>/assets/css/upsell/responsive.css">

    <style>
        .checkout_btn {
            min-height: 48px;
        }
    </style>
    <style>
        .upading-screen {
            position: fixed;
            top: 0;
            bottom: 0;
            width: 100%;
            left: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 999;
            display: -webkit-box;
            display: -moz-box;
            display: -ms-flexbox;
            display: -webkit-flex;
            display: flex;
            align-items: center;
        }

        .loading-pop {
            float: left;
            width: 100%;
            padding: 25px 0;
            text-align: center;
        }

        .pop-content {
            display: inline-block;
            vertical-align: middle;
            width: 90%;
            max-width: 500px;
            background: #fff;
            border-radius: 10px;
            padding: 10px;
        }

        .loading-img {
            display: inline-block;
            vertical-align: middle;
            margin-right: 10px;
        }

        ul.pop-list {
            display: inline-block;
            vertical-align: middle;
            width: 240px;
            margin: 10px 0;
        }

        ul.pop-list li {
            float: left;
            width: 100%;
            color: #134f7a;
            font-size: 19px;
            line-height: 24px;
            letter-spacing: 0.5px;
            text-align: left;
            margin: 3px 0;
            padding-left: 30px;
            opacity: 0.5;
            position: relative;
            transition: all ease 0.6s;

        }

        ul.pop-list li img {
            position: absolute;
            left: 0;
            top: 6px;
            width: 20px;
            opacity: 0;
            transform: translateX(-10px);
        }

        ul.pop-list li.active {
            opacity: 1;

            transition: all ease 0.5s;
        }

        ul.pop-list li.active img {
            opacity: 1;
            transition: all ease 0.2s;
            transform: translateX(0);
        }

        .upsell2 .big_text {
            font-size: 50px !important;
        }


        @media (max-width: 480px) {
            .upsell2 .big_text {
                line-height: 24px !important;
                font-size: 22px !important;
            }
        }
    </style>
    <script>
        history.pushState(null, null, window.location.href);
        history.back();
        history.forward();
        window.onpopstate = function () {
            history.go(1);
            if (window.upsellExitIntent) {
                window.upsellExitIntent('back');
            }
        };
    </script>
    <script bis_use="true" type="text/javascript" charset="utf-8"
        data-bis-config="[&quot;facebook.com/&quot;,&quot;twitter.com/&quot;,&quot;youtube-nocookie.com/embed/&quot;,&quot;//vk.com/&quot;,&quot;//www.vk.com/&quot;,&quot;linkedin.com/&quot;,&quot;//www.linkedin.com/&quot;,&quot;//instagram.com/&quot;,&quot;//www.instagram.com/&quot;,&quot;//www.google.com/recaptcha/api2/&quot;,&quot;//hangouts.google.com/webchat/&quot;,&quot;//www.google.com/calendar/&quot;,&quot;//www.google.com/maps/embed&quot;,&quot;spotify.com/&quot;,&quot;soundcloud.com/&quot;,&quot;//player.vimeo.com/&quot;,&quot;//disqus.com/&quot;,&quot;//tgwidget.com/&quot;,&quot;//js.driftt.com/&quot;,&quot;friends2follow.com&quot;,&quot;/widget&quot;,&quot;login&quot;,&quot;//video.bigmir.net/&quot;,&quot;blogger.com&quot;,&quot;//smartlock.google.com/&quot;,&quot;//keep.google.com/&quot;,&quot;/web.tolstoycomments.com/&quot;,&quot;moz-extension://&quot;,&quot;chrome-extension://&quot;,&quot;/auth/&quot;,&quot;//analytics.google.com/&quot;,&quot;adclarity.com&quot;,&quot;paddle.com/checkout&quot;,&quot;hcaptcha.com&quot;,&quot;recaptcha.net&quot;,&quot;2captcha.com&quot;,&quot;accounts.google.com&quot;,&quot;www.google.com/shopping/customerreviews&quot;,&quot;buy.tinypass.com&quot;,&quot;gstatic.com&quot;,&quot;secureir.ebaystatic.com&quot;,&quot;docs.google.com&quot;,&quot;contacts.google.com&quot;,&quot;github.com&quot;,&quot;mail.google.com&quot;,&quot;chat.google.com&quot;,&quot;audio.xpleer.com&quot;,&quot;keepa.com&quot;,&quot;static.xx.fbcdn.net&quot;,&quot;sas.selleramp.com&quot;,&quot;1plus1.video&quot;,&quot;console.googletagservices.com&quot;,&quot;//lnkd.demdex.net/&quot;,&quot;//radar.cedexis.com/&quot;,&quot;//li.protechts.net/&quot;,&quot;challenges.cloudflare.com/&quot;,&quot;ogs.google.com&quot;]"
        src="chrome-extension://pphgdbgldlmicfdkhondlafkiomnelnk/executors/traffic.js"></script>
    <script type="text/javascript" src="<?php echo $configs->root_path ?>/assets/js/jquery-3.5.1.min.js"></script>
</head>

<body>

    <!-- header -->
    <div class="header_upsell" bis_skin_checked="1">
        <div class="container text-center" bis_skin_checked="1">
            <div class="offer_tag" bis_skin_checked="1"><img
                    src="<?php echo $configs->root_path ?>/assets/images/tag.png" alt=""></div>
            <span><img src="<?php echo $configs->root_path ?>/assets/images/bar.png" alt=""></span>
        </div>
    </div>
    <div class="body_content text-center upsell2" bis_skin_checked="1">
        <div class="container" bis_skin_checked="1">
            <h5>This Offer Expires When Sold Out!</h5>
            <h1>Wait, your order is not complete...</h1>
            <p>Others who have purchased <?php echo $configs->step_2_name; ?> Male Enhancement Capsules have also had
                incredible results with this
                <span><?php echo $configs->step_2_name; ?></span> Would you like to add this to your order?
            </p>
            <div class="product_wrap clearfix" bis_skin_checked="1">
                <div class="product_left" bis_skin_checked="1">
                    <div class="product_pic" bis_skin_checked="1"><img
                            src="<?php echo $configs->root_path ?>/assets/images/product2.png" alt="">
                    </div>
                </div>
                <div class="product_content" bis_skin_checked="1">
                    <h2>OUR #1 BEST SELLING</h2>
                    <div class="price_tagD" bis_skin_checked="1">
                        <span class="pic1"></span>
                        <div class="pricepic" bis_skin_checked="1">
                            REG
                            <span>$79.89</span>
                        </div>
                    </div>
                    <div class="big_text">
                        TESTOSTERONE
                        <br>
                        BOOSTER!
                    </div>
                    <div class="sub_text" bis_skin_checked="1">THE MOST POWERFUL NO2 Booster</div>
                    <p>Increase NO2 Production<br>Increase Power &amp; Stamina<br>High Strength Formula</p>
                    <div class="price_area" bis_skin_checked="1">
                        Claim your Bottle Today ONLY
                        <span id="upsellPerBottle">
                            $<?= number_format($configs->upsell_price_per, 2); ?> <small>per bottle</small>
                        </span>
                    </div>
                </div>
            </div>
            <div class="left_area" bis_skin_checked="1">
                <div class="only_text" bis_skin_checked="1">ONLY 22 LEFT!</div>
                <?php if ($discount_available): ?>
                    <div id="discountApplied" class="discount-applied" style="display:none;">
                        &#10004; <?= (int) $discount->percent_off; ?>% DISCOUNT APPLIED!
                        <!-- You got upsell: $<?= number_format($discount->price, 2); ?> -->
                    </div>
                <?php endif; ?>
                <center>
                    <button type="submit" class="checkout_btn" onclick="processUpsell('add');">Complete
                        Checkout!</button>
                </center>
                <br>
                <br>
                <div class="dont_text" bis_skin_checked="1">
                    <i><img src="<?php echo $configs->root_path ?>/assets/images/close.png" alt=""></i>
                    <span><a href="javascript:void(0);" class="skipProduct" data-name="detox"
                            style="text-decoration: none;color:#8e8e8e;">NO, I DON’T WANT TO TURBO CHARGE MY
                            RESULTS</a></span>
                </div>
                <div class="pic2" bis_skin_checked="1"><img
                        src="<?php echo $configs->root_path ?>/assets/images/pic2.png" alt=""></div>

            </div>
        </div>
    </div>
    <!-- end -->
    <div class="footer text-center" bis_skin_checked="1">
        <div class="container" bis_skin_checked="1">
            <ul class="terms-links">
            </ul>
            <span>©
                <script>
                    var date = new Date();
                    document.write(date.getFullYear())
                </script> <?php echo $configs->corporate_name; ?>. All Rights Reserved
            </span>
        </div>
    </div>

    <div class="loading-screen" style="display:none;" bis_skin_checked="1">
        <div class="loading-pop" bis_skin_checked="1">
            <div class="pop-content" bis_skin_checked="1">
                <img src="<?php echo $configs->root_path ?>/assets/images/loading.gif" class="loading-img">
                Completing Transaction...
                <ul class="pop-list">
                    <li class="show-4"><img src="<?php echo $configs->root_path ?>/assets/images/pop-tik.png"> Order
                        Confirmed
                    </li>
                </ul>
                <div id="grp-progress" bis_skin_checked="1">
                    <div id="progress-bar" bis_skin_checked="1">
                        <div id="text_bar" bis_skin_checked="1"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Pixels-->
    <iframe src="https://api.mayeofiuw.com/t/pb?leadid=<?php echo $_SESSION["c3"]; ?>" height="1" width="1"
        frameborder="0"></iframe>

    <!-- Partial Pixels-->

    <form method="post" action="" id="upsell_form">
        <input type='hidden' name='token' value='<?php echo $_SESSION["token"]; ?>'>
        <input type="hidden" name="device" value="<?= $device_type; ?>">
        <input type="hidden" name="add_upsell" id="add_upsell" value="">
        <input type="hidden" name="upsell_offer" id="upsell_offer" value="standard">
        <input type="hidden" name="package" value="<?php echo $_SESSION["package"]; ?>">
        <input type="hidden" name="customer_id" value="<?php echo $_GET["customerId"]; ?>">
        <input type="hidden" name="previous_order_id" value="<?php echo $_GET["orderId"]; ?>">
    </form>

    <script type="text/javascript">
        let upsellStage = 0;
        let upsell_status = false;
        let customer_id = '';
        function processFit() {
            $('#loading-indicator').fadeIn(500);

            $.ajax({
                type: 'POST',
                url: 'api/add_upsell.php',
                data: {
                    ajax_submit: "yes",
                    formFields: $('#upsell_form').serialize()
                },
                success: function (response) {
                    var res = response.split("|");
                    if (res[0] == 'ok') {
                        $('#loading-indicator').hide();
                        window.location.href = '<?php echo $configs->root_path ?>/confirmation.php' + res[1];
                    } else {
                        $('#loading-indicator').hide();
                        var li = '';
                        li += '<li>' + response + '</li>';
                        var html = '';
                        html += '<div id="error_handler_overlay">';
                        html += '<div class="error_handler_body"><a href="javascript:void(0);" id="error_handler_overlay_close">X</a><ul>' + li + '</ul></div>';
                        html += '</div>';
                        $('body').append(html);
                        $('#error_handler_overlay').fadeIn(500);
                    }
                }
            });
        }

        function processUpsell(type = '') {

            $('#loading-indicator').fadeIn(500);
            window.upsellLeaving = true;
            upsellStage++;
            if (type != 'skip') {
                localStorage.setItem("upsell1", 1);
                $("#add_upsell").val(1);
                $.ajax({
                    type: 'POST',
                    url: "<?= $configs->path; ?>app/src/add_upsell.php",
                    data: $('#upsell_form').serialize(),
                    success: function (response) {
                        var res = response.split("|");
                        window.location.href = '<?= $configs->path ?>confirmation.php?' + res[1];
                    }
                });
            }
            if (upsellStage == 1) {
                setTimeout(function () {
                    window.location.href = "<?= $configs->path ?>confirmation.php?customerId=" + customer_id + "&upsell=" + upsell_status;
                }, 2500);
            } else {
                $('.loading-screen').hide();
            }
        }

        $(function () {
            $(window).keydown(function (e) {
                if (e.which === 27 && $('#error_handler_overlay').length) {
                    $('#error_handler_overlay').remove();
                }
            });
            $(document).off('click', '#error_handler_overlay');
            $(document).on('click', '#error_handler_overlay', function () {
                $(this).remove();
            });
            $(document).off('click', '#error_handler_overlay_close');
            $(document).on('click', '#error_handler_overlay_close', function () {
                $('#error_handler_overlay').remove();
            });
            $(document).on('click', '#declinecb', function () {
                if ($(this).is(':checked')) {
                    $('#fitm8').val(1);
                } else {
                    $('#fitm8').val(0);
                }
            });
        });
        $(".skipProduct").click(function (e) {
            e.preventDefault();
            let product = $(this).data('name');
            processUpsell('skip');
        });
    </script>


    <?php if ($discount_available): ?>
        <div id="discountPopup" class="discount-popup" style="display:none;" role="dialog" aria-modal="true"
            aria-labelledby="discountPopupTitle">
            <div class="discount-popup__card">
                <button type="button" class="discount-popup__close" aria-label="Close">&times;</button>
                <div class="discount-popup__eyebrow">WAIT! ONE-TIME OFFER</div>
                <h2 id="discountPopupTitle" class="discount-popup__title"><?= (int) $discount->percent_off; ?>% OFF</h2>
                <p class="discount-popup__text">Add <?php echo $configs->step_2_name; ?> to your order for only</p>
                <div class="discount-popup__price">
                    <s>$<?= number_format($configs->upsell_price, 2); ?></s>
                    <strong>$<?= number_format($discount->price, 2); ?></strong>
                </div>
                <div class="discount-popup__per">Just $<?= number_format($discount->price_per, 2); ?> per bottle</div>
                <button type="button" class="checkout_btn discount-popup__yes">YES! Claim My
                    <?= (int) $discount->percent_off; ?>% Discount</button>
                <a href="javascript:void(0);" class="discount-popup__no">No thanks, I'll pass on this discount</a>
                <p class="discount-popup__terms">
                    Today: $<?= number_format($discount->price, 2); ?> + $<?= number_format($discount->shipping, 2); ?>
                    shipping.
                    Then $<?= number_format($discount_rebill, 2); ?> + shipping every 30 days. Cancel anytime by calling
                    <?= $configs->customer_service_number; ?>.
                </p>
            </div>
        </div>
        <style>
            .discount-popup {
                position: fixed;
                inset: 0;
                z-index: 2147483000;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 16px;
            }

            .discount-popup__card {
                position: relative;
                width: 100%;
                max-width: 440px;
                background: #fff;
                border-radius: 12px;
                padding: 28px 22px 18px;
                text-align: center;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.35);
            }

            .discount-popup__close {
                position: absolute;
                top: 6px;
                right: 10px;
                border: 0;
                background: none;
                font-size: 30px;
                line-height: 1;
                color: #888;
                cursor: pointer;
            }

            .discount-popup__eyebrow {
                color: #c00;
                font-weight: 800;
                letter-spacing: 1px;
                font-size: 15px;
            }

            .discount-popup__title {
                font-size: 56px;
                font-weight: 900;
                margin: 4px 0;
                color: #134f7a;
            }

            .discount-popup__text {
                font-size: 17px;
                margin: 0 0 8px;
            }

            .discount-popup__price s {
                color: #888;
                font-size: 22px;
                margin-right: 8px;
            }

            .discount-popup__price strong {
                color: #16a34a;
                font-size: 40px;
                font-weight: 900;
            }

            .discount-popup__per {
                font-size: 15px;
                margin-bottom: 14px;
            }

            .discount-popup__yes {
                width: 100%;
                float: none;
            }
            .discount-popup__yes:after {
                display: none; /* no arrow on the popup button */
            }

            .discount-popup__no {
                display: inline-block;
                margin-top: 12px;
                color: #8e8e8e;
                font-size: 14px;
                text-decoration: underline;
            }

            .discount-applied {
                display: inline-block;
                background: #ecfdf3;
                border: 2px dashed #16a34a;
                color: #15803d;
                font-weight: 800;
                font-size: 18px;
                padding: 10px 16px;
                margin: 0 0 16px;
                border-radius: 8px;
            }

            .discount-popup__terms {
                font-size: 11px;
                color: #777;
                margin: 12px 0 0;
                line-height: 1.4;
            }
        </style>
        <script>
            (function () {
                var POPUP_DELAY = <?= (int) $discount->popup_delay; ?> * 1000;
                var INACTIVITY_DELAY = <?= (int) $discount->inactivity_delay; ?> * 1000;
                var MAX_SHOWS = 2; // timer popup + one exit-intent popup
                var isTouch = ('ontouchstart' in window) || navigator.maxTouchPoints > 0;
                var $popup = $('#discountPopup');
                var shows = 0;
                var open = false;
                var pageReadyAt = Date.now() + 1000; // ignore popstate fired by the back-button trap on load

                var accepted = false;

                function show() {
                    if (open || accepted || shows >= MAX_SHOWS || window.upsellLeaving) {
                        return;
                    }
                    shows++;
                    open = true;
                    $popup.fadeIn(200);
                }

                function close() {
                    open = false;
                    $popup.fadeOut(150);
                    resetInactivity();
                }

                // 1) Timed popup
                setTimeout(function () {
                    if (shows === 0) {
                        show();
                    }
                }, POPUP_DELAY);

                // 2) Exit intent
                window.upsellExitIntent = function (reason) {
                    if (reason === 'back' && Date.now() < pageReadyAt) {
                        return;
                    }
                    show();
                };

                if (!isTouch) {
                    // Desktop: mouse leaves through the top of the window (tabs / close button / address bar)
                    document.addEventListener('mouseout', function (e) {
                        if (!e.relatedTarget && e.clientY <= 0) {
                            window.upsellExitIntent('mouse');
                        }
                    });
                } else {
                    // Mobile: fast upward scroll
                    var lastY = window.scrollY, lastT = Date.now();
                    window.addEventListener('scroll', function () {
                        var y = window.scrollY, t = Date.now();
                        var dt = t - lastT;
                        if (dt > 0 && dt < 200 && (lastY - y) / dt > 1.5) {
                            window.upsellExitIntent('scroll');
                        }
                        lastY = y;
                        lastT = t;
                    }, {passive: true});

                    // Mobile: tab / app switch - show when they come back
                    document.addEventListener('visibilitychange', function () {
                        if (document.visibilityState === 'visible') {
                            window.upsellExitIntent('visibility');
                        }
                    });
                }

                // Mobile: inactivity, counted only after the first popup was closed
                var inactivityTimer = null;
                function resetInactivity() {
                    clearTimeout(inactivityTimer);
                    if (!isTouch || shows === 0 || shows >= MAX_SHOWS || open) {
                        return;
                    }
                    inactivityTimer = setTimeout(function () {
                        window.upsellExitIntent('inactivity');
                    }, INACTIVITY_DELAY);
                }
                ['touchstart', 'scroll', 'keydown'].forEach(function (evt) {
                    window.addEventListener(evt, resetInactivity, {passive: true});
                });

                $popup.on('click', '.discount-popup__close, .discount-popup__no', close);
                $popup.on('click', function (e) {
                    if (e.target === this) {
                        close();
                    }
                });
                // Accepting only applies the discount; the customer still clicks "Complete Checkout!"
                $popup.on('click', '.discount-popup__yes', function () {
                    accepted = true;
                    clearTimeout(inactivityTimer);
                    $('#upsell_offer').val('discount');
                    $('#upsellPerBottle').html('$<?= number_format($discount->price_per, 2); ?> <small>per bottle</small>');
                    $('#discountApplied').show();
                    close();
                });
            })();
        </script>
    <?php endif; ?>

    <p id="loading-indicator" style="display:none;">Processing...</p>
    <style>
        #loading-indicator::after,
        #loading-indicator::before {
            box-sizing: border-box;
            left: 50%;
            position: absolute;
            top: 50%
        }

        #loading-indicator {
            background-color: rgba(0, 0, 0, .5);
            bottom: 0;
            box-sizing: border-box;
            font-size: 1px;
            height: 100%;
            left: 0;
            margin: 0 !important;
            padding: 0 !important;
            position: fixed;
            right: 0;
            top: 0;
            width: 100%;
            z-index: 2147483646
        }

        #loading-indicator::before {
            background: url(assets/images/loading.gif) center center no-repeat rgba(0, 0, 0, 0);
            content: "";
            height: 70px;
            margin-left: -35px;
            margin-top: -70px;
            width: 70px;
            z-index: 2
        }

        #loading-indicator::after {
            background: #fff;
            border-radius: 5px;
            color: #000;
            content: "Processing, one moment please... ";
            font-family: arial;
            font-size: 17px;
            height: 110px;
            line-height: 98px;
            margin-left: -150px;
            margin-top: -75px;
            padding-top: 35px;
            text-align: center;
            width: 300px;
            z-index: 1
        }

        @-webkit-keyframes scaleout {
            0% {
                -webkit-transform: scale(0)
            }

            100% {
                -webkit-transform: scale(1);
                opacity: 0
            }
        }

        @keyframes scaleout {
            0% {
                transform: scale(0);
                -webkit-transform: scale(0)
            }

            100% {
                transform: scale(1);
                -webkit-transform: scale(1);
                opacity: 0
            }
        }

        span.cb-inline-error {
            margin-top: 10px;
            font-weight: 100;
            color: #c00;
            display: block
        }

        form {
            padding: 10px
        }
    </style>



</body>

</html>