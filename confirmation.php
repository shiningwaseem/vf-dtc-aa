<?php
include("_global.php");
?>
<!DOCTYPE html>

<html lang="en">


<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">


    <title><?= $configs->step1_name; ?></title>
    <meta name="description" content="<?= $configs->step1_name; ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

    <meta http-equiv="content-language" content="en-us">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="HandheldFriendly" content="true">
    <meta name="viewport" content="width=device-width,user-scalable=no">
    <link href="assets/css/app.css" rel="stylesheet">
    <link href="assets/images/favicon.png" rel="icon" type="image/png">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/ty-style.css">
    <style>
        .addNewD {
            text-align: center;
        }

        .pic_1 {
            display: inline-block;
        }
    </style>

    <script>
        history.pushState(null, null, window.location.href);
        history.back();
        history.forward();
        window.onpopstate = function () {
            history.go(1);
        };
    </script>

</head>

<body>
    <div class="container" bis_skin_checked="1">
        <div class="row" bis_skin_checked="1">
            <div class="col-md-12 col-sm-12 col-xs-12" bis_skin_checked="1">
                <div class="header clearfix" style="margin-bottom:15px;padding-bottom:5px;" bis_skin_checked="1">
                    <nav>
                    </nav>
                </div>
            </div>
        </div>
        <div class="row" bis_skin_checked="1">
            <div class="col" bis_skin_checked="1">
                <div id="success" class="alert alert-success" role="alert" bis_skin_checked="1">
                    <span class="lead text-center">
                        <strong>Thank You!</strong> Your order will be shipped within 1 business day! You can expect it
                        to arrive in 3-5 days after shipment.
                    </span>
                </div>
            </div>
        </div>
        <style>
            .descriptor {
                margin-bottom: 16px;
            }

            .descriptor .col {
                text-align: center;
            }

            .descriptor .col div {
                padding: 10px 8px 10px 8px;
                background: #F6F5EF;
                border-radius: 8px;
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }

            .descriptor b {
                color: #212529;
                font-weight: 400;
                font-size: 20px;
                line-height: 30px;
            }

            .descriptor span {
                background: #FFDA7C;
                padding: 2px 8px 4px 8px;
                white-space: nowrap;
                border-radius: 8px;
                margin: 0 8px;

                font-weight: 600;
            }
        </style>

        <div class="row" bis_skin_checked="1">
            <div class="col-sm-12 col-md-6 text-center" bis_skin_checked="1">
                <img src="assets/images/product.png" width="245" height="205" class="img-responsive pic_1" alt="">
            </div>
            <div class="col-sm-12 col-md-6" bis_skin_checked="1">
                <h1 style="display: flex; align-items: center; gap: 8px;">
                    <img src="assets/images/checkmark.gif" width="55" height="55" alt="">
                    Your Order is Confirmed
                </h1>
                <p>We are happy you have chosen the most powerful male enhancement all natural supplement formula and
                    the hottest products on the market.</p>
                <p>Your order is currently being processed and will be shipped within 1 business day. You’ll receive a
                    shipping confirmation email with a tracking number once your product ships. If for some reason you
                    don’t
                    receive that email, contact our customer service team immediately so we may investigate the issue –
                    US:
                    <span id="gc-number-1" class="gc-cs-link" title="Call with Google Voice"><a
                            href="tel:<?php echo $configs->customer_service_number; ?>"><?php echo $configs->customer_service_number; ?></a></span>
                </p>
                <h4 class="commit">We are 100% committed to your product satisfaction and the most powerful male
                    enhancement</h4>
                <style>
                    h4.commit {
                        background: #F6FAF7;
                        border-left: 8px solid #3A6544;
                        padding: 8px 0px 8px 24px;
                        border-radius: 8px 0 0 8px;
                        font-weight: 600;
                        font-size: 24px;
                        line-height: 32px;
                    }
                </style>
                <p>
                    <span> Call or Email us if you have any problems with your purchase at
                        <span id="gc-number-2" class="gc-cs-link" title="Call with Google Voice">
                            <a
                                href="tel:<?php echo $configs->customer_service_number; ?>"><?php echo $configs->customer_service_number; ?></a>
                        </span> or
                        <span class="gc-cs-link">
                            <!-- <a href="mailto:care@get<?php echo $configs->step_1_name; ?>.com">care@get<?php echo $configs->step_1_name; ?>.com</a> -->
                        </span>
                    </span>
                </p>

                <h4>Order Summary</h4>
                <table class="table">
                    <tbody>
                        <tr>

                            <?php if (isset($_SESSION["package"]) && $_SESSION["package"] == 5): ?>
                                <td><?php echo $configs->package_5_description; ?></td>
                                <td>$<?php
                                $price = $configs->bottle_per_5;
                                echo $price;
                                ?>/each</td>
                            <?php elseif (isset($_SESSION["package"]) && $_SESSION["package"] == 3): ?>
                                <td><?php echo $configs->package_3_description; ?></td>
                                <td>$<?php
                                $price = $configs->bottle_per_3;
                                echo $price;
                                ?>/each</td>
                            <?php else: ?>
                                <td><?php echo $configs->package_1_description; ?></td>
                                <td>$<?php
                                $price = $configs->bottle_per_1;
                                echo $price;
                                ?>/each</td>
                            <?php endif; ?>
                        </tr>


                        <!-- <?php
                        $upsell_price = 0;
                        if (isset($_SESSION["added_upsell"]) && $_SESSION["added_upsell"] === true):
                            $upsell_price = isset($_SESSION["upsell_price_charged"]) ? $_SESSION["upsell_price_charged"] : $configs->upsell_price;
                            ?>
                        <tr class="showDetox">
                            <td><?php echo $configs->step2_name; ?>  (2 bottles)</td>
                            <td>$<?php echo $upsell_price; ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="showFitnessTracker">
                            <td><?= $configs->product_3['name']; ?></td>
                            <td>
                                <?php
                                $smart_watch_price = number_format($configs->product_3['discount_price'], 2);
                                echo '$' . $smart_watch_price;
                                ?>
                            </td>
                        </tr>
                        <tfoot>
                            <tr>
                                <th>TOTAL</th>
                                <th>$<span id="totalPrice"></span></th>
                            </tr>
                        </tfoot> -->
                    </tbody>

                </table>
            </div>
        </div>

        <div class="row" bis_skin_checked="1">
            <div class="col text-center" bis_skin_checked="1">
                <img src="assets/images/footer-logos-image.png" width="960" height="87"
                    class="center-block img-responsive" alt="">
                <br>
                <div class="col-lg-12 content centered" style="font-size:12px;" bis_skin_checked="1">
                    <div id="disclaimers" bis_skin_checked="1">
                        <p></p>
                        <br>
                        <br>
                        <center>
                            <?php echo $configs->step_1_name; ?> is committed to maintaining the highest quality
                            products and the
                            utmost integrity in business practices. All products sold on this website are certified by
                            Good
                            Manufacturing Practices (GMP), which is the highest standard of testing in the supplement
                            industry.
                        </center>
                        <p></p>
                        <p></p>
                        <p></p>
                        <center>
                            Notice: The products and information found on this site are not intended to replace
                            professional
                            medical advice or treatment. These statements have not been evaluated by the Food and Drug
                            Administration. These products are not intended to diagnose, treat, cure or prevent any
                            disease.
                            Individual results may vary.
                        </center>
                        <br>
                        <br>
                        <center>
                            [ <a href="javascript:void(0);"
                                onclick="openNewWindow(&#39;disclosures/terms.php&#39;,&#39;modal&#39;);">Terms &amp;
                                Conditions</a> ]
                            [ <a href="javascript:void(0);"
                                onclick="openNewWindow(&#39;disclosures/privacy.php&#39;,&#39;modal&#39;);">Privacy
                                Policy</a> ]
                            [ <a href="javascript:void(0);"
                                onclick="openNewWindow(&#39;disclosures/wireless.php&#39;,&#39;modal&#39;);">Wireless
                                Policy</a> ]
                            <br>
                        </center>
                        <br>
                        <br>©
                        <script type="text/javascript">var year = new Date();
                            document.write(year.getFullYear());</script> <?= $configs->corporate_name; ?>, All rights
                        reserved
                        <br>
                        <p></p>
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript" src="assets/js/jquery-3.5.1.min.js"></script>
        <script type="text/javascript">
            // Popup JS
            function openNewWindow(page_url, type, window_name, width, height, top, left, features) {
                if (!type) {
                    type = 'popup';
                }

                if (!width) {
                    width = 480;
                }

                if (!height) {
                    height = 480;
                }

                if (!top) {
                    top = 50;
                }

                if (!left) {
                    left = 50;
                }

                if (!features) {
                    features = 'resizable,scrollbars';
                }

                if (type == 'popup') {
                    var settings = 'height=' + height + ',';
                    settings += 'width=' + width + ',';
                    settings += 'top=' + top + ',';
                    settings += 'left=' + left + ',';
                    settings += features;

                    win = window.open(page_url, window_name, settings);
                    win.window.focus();
                } else if (type == 'modal') {
                    var html = '';
                    html += '<div id="app_common_modal">';
                    html += '<div class="app_modal_body"><a href="javascript:void(0);" id="app_common_modal_close">X</a><iframe src="' + page_url + '" frameborder="0"></iframe></div>';
                    html += '</div>';

                    if (!$('#app_common_modal').length) {

                        $('body').append(html);
                    }
                    $('#app_common_modal').fadeIn();
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

                $(document).on('click', '#app_common_modal_close', function () {
                    $('#app_common_modal').remove();
                });
            });
        </script>


    </div>
</body>

</html>