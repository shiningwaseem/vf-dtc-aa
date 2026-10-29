<?php
include("_global.php");
$states = array("AL" => "Alabama (AL)", "AK" => "Alaska (AK)", "AS" => "American Samoa (AS)", "AZ" => "Arizona (AZ)", "AR" => "Arkansas (AR)", "AE" => "Armed Forces (AE)", "AA" => "Armed Forces Americas (AA)", "AP" => "Armed Forces Pacific (AP)", "CA" => "California (CA)", "CO" => "Colorado (CO)", "CT" => "Connecticut (CT)", "DE" => "Delaware (DE)", "DC" => "District of Columbia (DC)", "FM" => "Federated States of Micronesia (FM)", "GA" => "Georgia (GA)", "FL" => "Florida (FL)", "HI" => "Hawaii (HI)", "GU" => "Guam (GU)", "IL" => "Illinois (IL)", "ID" => "Idaho (ID)", "IA" => "Iowa (IA)", "IN" => "Indiana (IN)", "KY" => "Kentucky (KY)", "KS" => "Kansas (KS)", "ME" => "Maine (ME)", "LA" => "Louisiana (LA)", "MA" => "Massachusetts (MA)", "MD" => "Maryland (MD)", "MN" => "Minnesota (MN)", "MI" => "Michigan (MI)", "MO" => "Missouri (MO)", "MS" => "Mississippi (MS)", "NE" => "Nebraska (NE)", "MT" => "Montana (MT)", "NH" => "New Hampshire (NH)", "NV" => "Nevada (NV)", "NM" => "New Mexico (NM)", "NJ" => "New Jersey (NJ)", "NC" => "North Carolina (NC)", "NY" => "New York (NY)", "MP" => "Northern Mariana Islands (MP)", "ND" => "North Dakota (ND)", "OK" => "Oklahoma (OK)", "OH" => "Ohio (OH)", "PA" => "Pennsylvania (PA)", "OR" => "Oregon (OR)", "MH" => "Republic of Marshall Islands (MH)", "PR" => "Puerto Rico (PR)", "SC" => "South Carolina (SC)", "RI" => "Rhode Island (RI)", "TN" => "Tennessee (TN)", "SD" => "South Dakota (SD)", "UT" => "Utah (UT)", "TX" => "Texas (TX)", "VI" => "Virgin Islands of the U.S. (VI)", "VT" => "Vermont (VT)", "WA" => "Washington (WA)", "VA" => "Virginia (VA)", "WI" => "Wisconsin (WI)", "WV" => "West Virginia (WV)", "WY" => "Wyoming (WY)");
if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = generate_csrf_token();
}

if ($mobile_detect->isMobile() && !$mobile_detect->isTablet()) {
    $url = $configs->mobile_path . "?" . $_SERVER['QUERY_STRING'];
    header("Location: $url");
}
?>
<!DOCTYPE html>

<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <title><?= $configs->step1_name; ?></title>
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <link href="assets/images/favicon.png" rel="icon" type="image/png">

    <link href="assets/css/app.css" rel="stylesheet">
    <link href="assets/css/checkout.css" type="text/css" rel="stylesheet">
    <link href="assets/css/modal.css" type="text/css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Oswald:wght@400;500;600;700&family=Roboto:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        .chkbox {
            width: auto !important;
            height: auto !important;
            appearance: checkbox !important;
            outline: auto !important;
            display: inline !important;
            text-align: center !important;
        }

        label.payment_as_shipping_label {
            text-align: center;
            display: inline;
            font-size: 16px !important;
        }

        /*.pulse {
            -webkit-transition: .3s ease !important;
            -o-transition: .3s ease !important;
            transition: .3s ease !important;
            animation: 1s pulse infinite alternate ease-in-out !important;
            position: relative !important;
        }*/
    </style>
    <script type="text/javascript">
        function getDate(days) {
            var monthNames = new Array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
            var now = new Date();
            now.setDate(now.getDate() + days);
            var nowString = monthNames[now.getMonth()] + " " + now.getDate() + ", " + now.getFullYear();
            document.write(nowString);
        }
    </script>

    <script>
        history.pushState(null, null, window.location.href);
        history.back();
        history.forward();
        window.onpopstate = function () {
            history.go(1);
        };
    </script>
    <style>
        .frmCheckElemts {
            margin-top: 15px;
            text-align: center;
        }

        .frmCheckElemts label {
            font-size: 11px !important;
            font-weight: normal;
        }

        form {
            padding: 0;
        }
    </style>

</head>

<body class="checkout">
    <div class="wrapper" bis_skin_checked="1">
        <div id="app" bis_skin_checked="1">
            <header class="header">
                <div class="header__warning">
                    <div class="container">
                        <span class="alert-color">Warning:</span> Due to extremely high media demand, there is limited
                        supply of <span class="product-name"><?php echo trim($configs->step_1_name); ?></span> in stock
                        as
                        of <span class="date-container">
                            <b></b>
                            <?php echo date("F j, Y"); ?>
                        </span>
                        <span class="hurry">HURRY! <span id="demo2"></span>
                        </span>
                    </div>
                </div>
                <div class="container row">
                    <img src="assets/images/logo2-strips.png" alt="" class="header__logo">
                    <div class="header__info row">
                        <div class="delivery-block" style="max-width:250px"> Internet Exclusive Offers Available to
                            North
                            Americans Residents Only </div>
                    </div>
                </div>
            </header>
            <!----partial pixel here------->
            <iframe src="https://api.mayeofiuw.com/t/pb?leadid=<?php echo $_SESSION["c3"]; ?>&event=4815" height="1"
                width="1" frameborder="0"></iframe>
            <div class="order" bis_skin_checked="1">
                <div class="container" bis_skin_checked="1">
                    <div class="row" bis_skin_checked="1">
                        <div class="order__left" bis_skin_checked="1">
                            <div class="steps" bis_skin_checked="1">
                                <ul class="steps__list row">
                                    <li class="steps__item">1. Shipping Info</li>
                                    <li class="steps__item active">2. Finish Order</li>
                                    <li class="steps__item">3. Summary</li>
                                </ul>
                                <div class="approved-text" bis_skin_checked="1">
                                    <strong><span class="emphasis">APPROVED!</span> Free Bottle Packages
                                        Confirmed</strong>
                                </div>
                                <p>Limited supply available as of <span class="full-date date-container"><b>Monday,
                                            6/23/2025</b></span>. We
                                    currently have product <strong>in stock</strong> and ready to ship within 24 hours.
                                </p>
                                <p class="status-pr">Sell Out Risk: <span>HIGH</span></p>
                            </div>

                            <div class="product-selection" bis_skin_checked="1">
                                <div class="product product1 sel-prod active" data-pkg="5" bis_skin_checked="1">
                                    <div class="package-item">
                                        <div class="package-item__header">
                                            <div class="title-block">
                                                <span class="title-block__main">BUY 3 GET 2 FREE</span>
                                            </div>
                                            <div class="shipping-row">
                                                <span>FREE SHIPPING</span>
                                            </div>
                                        </div>

                                        <div class="package-item__content">
                                            <div class="package-item__status">
                                                <span></span>
                                            </div>
                                            <div class="package-images" style="margin-bottom:15px;">
                                                <div class="package-images__item">
                                                    <img class="product1-image1" alt="" src="assets/images/product.png">
                                                    <img class="product1-image2" alt="" src="assets/images/product.png">
                                                    <img class="product1-image3" alt="" src="assets/images/product.png">
                                                </div>
                                                <div class="package-images__item">
                                                    <img class="product1-image4" alt="" src="assets/images/product.png">
                                                    <img class="product1-image5" alt="" src="assets/images/product.png">
                                                </div>
                                                <div class="package__save">
                                                    <span class="package__save_title">SAVE</span>
                                                    <span class="package__save_item save-price"><span
                                                            class="product-save-price">$
                                                            <span><?= number_format($configs->bottle_save_5, 2); ?></span></span></span>
                                                </div>
                                            </div>
                                            <div class="package-info">
                                                <span class="package-info__title">5 Bottle Performance Pack</span>
                                                <span class="package-info__price">
                                                    <p class="price-first"></p>
                                                    <span>
                                                        <span class="price-top"><span class="product-single-price">$
                                                                <span><?= number_format($configs->bottle_per_5, 2); ?></span></span></span>
                                                        <br>bottle
                                                    </span>
                                                </span>
                                                <span class="package-info__retail"> Retail - <span><span
                                                            class="product-sale-single-price">$
                                                            <span><?= number_format($configs->bottle_retail_5 * 5, 2); ?></span></span></span>
                                                </span>
                                                <span class="package-info__btn">Selected!</span>
                                            </div>
                                            <div class="timer-block"> Acceptance of applications ends in: <span
                                                    id="demo"><span id="time" class="count-down-timer">14 :
                                                        54</span></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product product2 sel-prod" data-pkg="3" bis_skin_checked="1">
                                    <div class="package-item">
                                        <div class="package-item__header">
                                            <div class="title-block">
                                                <span class="title-block__main">BUY 2 GET 1 FREE</span>
                                            </div>
                                            <div class="shipping-row">
                                                <span>FREE SHIPPING</span>
                                            </div>
                                        </div>

                                        <div class="package-item__content">
                                            <div class="package-item__status">
                                                <span></span>
                                            </div>
                                            <div class="package-images">
                                                <div class="package-images__item">
                                                    <img class="product2-image1" alt="" src="assets/images/product.png">
                                                    <img class="product2-image2" alt="" src="assets/images/product.png">
                                                </div>
                                                <div class="package-images__item">
                                                    <img class="product2-image3" alt="" src="assets/images/product.png">
                                                </div>
                                                <div class="package__save">
                                                    <span class="package__save_title">SAVE</span>
                                                    <span class="package__save_item save-price"><span
                                                            class="product-save-price">$
                                                            <span><?= number_format($configs->bottle_save_3, 2); ?></span></span></span>
                                                </div>
                                            </div>
                                            <div class="package-info">
                                                <span class="package-info__title">3 Bottle Performance Pack</span>
                                                <span class="package-info__price">
                                                    <p class="price-first"></p>
                                                    <span>
                                                        <span class="price-top"><span class="product-single-price">$
                                                                <span><?= number_format($configs->bottle_per_3, 2); ?></span></span></span>
                                                        <br>bottle
                                                    </span>
                                                </span>
                                                <span class="package-info__retail"> Retail - <span><span
                                                            class="product-sale-single-price">$
                                                            <span><?= number_format($configs->bottle_retail_3 * 3, 2); ?></span></span></span>
                                                </span>
                                                <span class="package-info__btn">Select Package</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="product product3 sel-prod" data-pkg="1" bis_skin_checked="1">
                                    <div class="package-item">
                                        <div class="package-item__header">
                                            <div class="title-block">
                                                <span class="title-block__main">BUY 1 GET 1 FREE*</span>
                                            </div>
                                            <div class="shipping-row">
                                                <span>FREE SHIPPING</span>
                                            </div>
                                        </div>

                                        <div class="package-item__content">
                                            <div class="package-item__status">
                                                <span></span>
                                            </div>
                                            <div class="package-images">
                                                <div class="package-images__item">
                                                    <img alt="" src="assets/images/product.png">
                                                </div>
                                                <div class="package-images__item">
                                                    <img alt="" src="assets/images/product.png">
                                                </div>
                                                <div class="package__save">
                                                    <span class="package__save_title">SAVE</span>
                                                    <span class="package__save_item save-price"><span
                                                            class="product-save-price">$
                                                            <span><?= number_format($configs->bottle_save_1, 2); ?></span></span></span>
                                                </div>
                                            </div>
                                            <div class="package-info">
                                                <span class="package-info__title">2 Bottle Performance Pack</span>
                                                <span class="package-info__price">
                                                    <p class="price-first"></p>
                                                    <span>
                                                        <span class="price-top"><span class="product-single-price">$
                                                                <span><?= number_format($configs->bottle_per_1, 2); ?></span></span></span>
                                                        <br>bottle
                                                    </span>
                                                </span>
                                                <span class="package-info__retail"> Retail - <span><span
                                                            class="product-sale-single-price">$
                                                            <span><?= number_format($configs->bottle_retail_1 * 2, 2); ?></span></span></span>
                                                </span>
                                                <span class="package-info__btn">Select Package</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="secure-icons-contain">
                                    <img class="img-responsive secure-icons" src="assets/images/or-secureicons.png">
                                </div>
                                <div class="guarantee-block" bis_skin_checked="1">
                                    <div class="guarantee-top" bis_skin_checked="1">
                                        30 day money back guarantee
                                    </div>
                                    <div class="guarantee-content" bis_skin_checked="1">
                                        <img alt="" class="guarantee-icon" src="assets/images/30-money.png">
                                        <div class="guarantee-text" bis_skin_checked="1">
                                            <p>We are so confident in our products and services, that we back it with a
                                                30
                                                day money back guarantee. If for any reason you are not fully satisfied
                                                with
                                                our products, simply return the purchased products in the original
                                                container
                                                within 30 days of when you received your order. We will refund you 100%
                                                of
                                                the purchase price - with absolutely no hassle.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="order__right" bis_skin_checked="1">
                            <div class="form__header" bis_skin_checked="1">
                                <h2>FINAL STEP:</h2>
                                <h3>PAYMENT INFORMATION</h3>
                            </div>
                            <div class="form__in" bis_skin_checked="1">
                                <span class="accept-text">We Accept:</span>
                                <ul class="form__cards">
                                    <li><img alt="" src="assets/images/visa.png"></li>
                                    <li><img alt="" src="assets/images/mastercard.png"></li>
                                </ul>
                                <form class="form-popup" id="checkout" onsubmit="return submit_form()">
                                    <input type='hidden' name='token' value='<?= $_SESSION["token"]; ?>'>

                                    <input type="hidden" name="package"
                                        value="<?= (isset($_SESSION["package"])) ? $_SESSION["package"] : 5; ?>"
                                        id="package">
                                    <input type="hidden" name="fields_prospect_id"
                                        value="<?= isset($_GET["prospectId"]) ? $_GET["prospectId"] : ''; ?>">

                                    <input type="hidden" name="fields_cc_type" value="">
                                    <input type="hidden" name="device" value="desktop">
                                    <input type="hidden" name="iscreditcardPasted" id="iscreditcardPasted" value="0">
                                    <!-- <input type="hidden" name="shipping" id="shipping_id" value="2"> -->
                                    <!-- <input type="hidden" name="campaign_id" id="campaign_id" value="204"> -->
                                    <!-- <input type="hidden" name="campaign_id_sc" id="campaign_id_sc" value="206"> -->
                                    <!-- <input type="hidden" name="prepaid_campaign_id" id="prepaid_campaign_id" value="204"> -->
                                    <input type="hidden" name="billingSameAsShipping" id="billingSameAsShipping"
                                        value="1">
                                    <input type="hidden" id="billing_country" name="billing_country" value="US">
                                    <input type="hidden" id="country" name="country" value="US">
                                    <!-- <input type="hidden" name="notes" id="notes" value=""> -->
                                    <input type="hidden" id="cc_type" name="cc_type" value="">
                                    <!-- <input type="hidden" id="device_finger_print" name="device_finger_print" value=""> -->
                                    <!-- <input type="hidden" id="gateway_id" name="gateway_id" value=""> -->

                                    <div class="shipping-address-block" bis_skin_checked="1">
                                        <h4 class="shipping-title">Shipping address</h4>
                                        <div class="form-group">
                                            <input type="text" class="form-control required" id="shipping_fname"
                                                name="fields_shipping_fname" placeholder="First Name"
                                                autocomplete="given-name">
                                        </div>
                                        <div class="form-group">
                                            <input type="text" class="form-control required" id="shipping_lname"
                                                name="fields_shipping_lname" placeholder="Last Name"
                                                autocomplete="family-name">
                                        </div>
                                        <div class="form-group">
                                            <input type="text" class="form-control required" id="shipping_address1"
                                                name="fields_shipping_address1" placeholder="Address"
                                                autocomplete="address-line1">
                                        </div>
                                        <div class="form-group">
                                            <input type="text" class="form-control" id="shipping_address2"
                                                name="fields_shipping_address2"
                                                placeholder="Apartment, suite, etc. (optional)"
                                                autocomplete="address-line2">
                                        </div>
                                        <div class="form-group">
                                            <input type="text" class="form-control required" id="shipping_city"
                                                name="fields_shipping_city" placeholder="City"
                                                autocomplete="address-level2">
                                        </div>
                                        <div class="form-group">
                                            <select class="form-control" id="shipping_country"
                                                name="fields_shipping_country">
                                                <option value="US" selected>United States</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <select class="form-control required" id="shipping_state"
                                                name="fields_shipping_state">
                                                <option value="" selected>Choose State</option>
                                                <?php foreach ($states as $key => $state) {
                                                    echo "<option value='$key'>$state</option>";
                                                } ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <input type="tel" class="form-control required" id="shipping_zip"
                                                name="fields_shipping_zip" placeholder="Zip code" inputmode="numeric"
                                                maxlength="5" minlength="5" autocomplete="postal-code"
                                                onkeyup="javascript: this.value = this.value.replace(/[^0-9]/g, &#39;&#39;);">
                                        </div>
                                        <div class="form-group">
                                            <input type="tel" class="form-control required" id="shipping_phone"
                                                name="fields_shipping_phone" placeholder="Phone" inputmode="numeric"
                                                maxlength="12" autocomplete="tel">
                                        </div>
                                        <div class="form-group">
                                            <input type="email" class="form-control required" id="shipping_email"
                                                name="fields_shipping_email" placeholder="Email" autocomplete="email">
                                        </div>
                                    </div>


                                    <label for="payment_as_shipping" class="payment_as_shipping_label">
                                        <input type="checkbox" name="" id="payment_as_shipping" checked=""
                                            class="chkbox bill-inp">
                                        <span>Billing same as Shipping</span>
                                    </label>

                                    <div class="billing-info" style="display:none;" bis_skin_checked="1">
                                        <div class="billing-form" bis_skin_checked="1">
                                            <span class="billing-title">Billing Info</span>

                                            <div id="billingAddress" class="form-holder"
                                                placeholder="Enter your street address (ex: 123 street)"
                                                bis_skin_checked="1">
                                                <input type="text" name="fields_billing_address1"
                                                    id="billing_street_address" placeholder="Address"
                                                    class="form-control required">
                                            </div>

                                            <div class="form-holder" placeholder="City*" bis_skin_checked="1">
                                                <input type="text" name="fields_billing_city" id="billing_city"
                                                    placeholder="City" class="form-control required">
                                            </div>


                                            <div class="form-holder" bis_skin_checked="1">
                                                <select name="country" placeholder="Country" class="form-control">
                                                    <option value="US">US</option>
                                                </select>
                                            </div>

                                            <select name="fields_billing_state" id="billing_state"
                                                class="form-control required">
                                                <option value="">Select Billing State</option>
                                                <?php foreach ($states as $key => $state) {
                                                    echo "<option value='$key'>$state</option>";
                                                } ?>
                                            </select>

                                            <div id="billingZipCode" class="form-holder" bis_skin_checked="1">
                                                <input type="text" name="fields_billing_zip" id="billing_postcode"
                                                    placeholder="Zip Code" class="form-control required" minlength="5"
                                                    maxlength="5"
                                                    onkeyup="javascript: this.value = this.value.replace(/[^0-9]/g, &#39;&#39;);">
                                            </div>
                                            <span class="billing-title">Payment Info</span>
                                        </div>
                                    </div>

                                    <div class=" margin-bottom-5" bis_skin_checked="1">
                                        <div class="phone-12 columns" bis_skin_checked="1">
                                            <label id="cc_number_label">Card Number:</label>
                                        </div>
                                    </div>

                                    <div class="phone-12 columns form-holder" bis_skin_checked="1">
                                        <input type="tel" name="fields_cc_number" id="cardNumber" data-threeds="pan"
                                            class="form-control required masked" placeholder="Card Number"
                                            maxlength="16" autocomplete="off"
                                            onkeyup="javascript: this.value = this.value.replace(/[^0-9]/g, &#39;&#39;);">
                                        <div class="cc-text-validation-error" style="display: none;"
                                            bis_skin_checked="1">*Incorrect Credit Card number.</div>
                                        <div class="accept-icon" bis_skin_checked="1"></div>
                                        <?php if (!empty($configs->card_discount->enabled)) { ?>
                                        <div id="card-discount-note" data-amount="<?= number_format((float)$configs->card_discount->amount, 0); ?>" style="display:none;margin:6px 0 10px;padding:8px 10px;border-radius:4px;background:#e8f7ec;border:1px solid #2e9e4f;color:#17692f;font-weight:700;font-size:13px;line-height:1.3;"></div>
                                        <?php } ?>
                                    </div>

                                    <div class="row margin-bottom-5" bis_skin_checked="1">
                                        <div class="phone-12 columns" bis_skin_checked="1">
                                            <label>Card Expiry Date:</label>
                                        </div>
                                    </div>

                                    <div class="row" bis_skin_checked="1">
                                        <div class="phone-6 columns form-holder" bis_skin_checked="1">
                                            <select name="fields_expmonth" data-threeds="month" id="fields_expmonth"
                                                class="required form-control" autocomplete="off"
                                                data-error-message="Please select a valid expiry month!">
                                                <option value="">Month</option>
                                                <?php
                                                for ($count = 1; $count <= 12; $count++) {
                                                    $month_num = ($count < 10 ? "0$count" : $count);
                                                    $month_name = date("F", strtotime('00-' . $month_num . '-01'));
                                                    echo "<option value='$month_num'>$month_num - $month_name</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <div class="phone-6 columns form-holder" bis_skin_checked="1">
                                            <select name="fields_expyear" data-threeds="year" id="fields_expyear"
                                                class="required form-control" autocomplete="off"
                                                data-error-message="Please select a valid expiry year!">
                                                <option value="">Year</option>
                                                <?php
                                                for ($yr = date('Y'); $yr <= 2040; $yr++) {
                                                    $yr_num = substr($yr, 2);
                                                    echo "<option value='$yr_num'>$yr</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row margin-bottom-5" bis_skin_checked="1">
                                        <div class="phone-12 columns" bis_skin_checked="1">
                                            <label>CVV:</label>
                                        </div>
                                    </div>

                                    <div class="row" bis_skin_checked="1">
                                        <div class="phone-6 columns form-holder" bis_skin_checked="1">
                                            <input type="tel" name="fields_cvv" id="fields_cvv"
                                                class="form-control fcheckout-field required" data-validate="cvv"
                                                maxlength="4" placeholder="•••" autocomplete="off"
                                                data-error-message="Please enter a valid CVV code!"
                                                onkeyup="javascript: this.value = this.value.replace(/[^0-9]/g, &#39;&#39;);">
                                        </div>
                                        <div class="phone-6 columns" bis_skin_checked="1">
                                            <span class="cvv-link ">
                                                <a class="ccvwhatsthis form-link cvvbox" href="javascript:void(0)"
                                                    style="margin: 9px 0px 0px 2px;" bis_skin_checked="1"> Where can I
                                                    find my Security Code?</a>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="cvv-image clear" style="display: none;" bis_skin_checked="1">
                                        <img alt="" src="assets/images/cvv-image.png">
                                    </div>
                                    <div class="clear" bis_skin_checked="1"></div>
                                    <div class="form__footer" bis_skin_checked="1">
                                        <div class="secure-icon" bis_skin_checked="1"><span>Secure 256-bit SSL
                                                Encryption</span></div>
                                        <button data-submit-button="" type="submit" class="send-btn pulse">
                                            <span>RUSH MY ORDER!</span>
                                        </button>
                                        <div class="terms_content_area" bis_skin_checked="1">
                                            <div class="check-box-n" bis_skin_checked="1">
                                                <input type="checkbox" class="agree-checkbox" id="agree-checkbox"
                                                    checked="checked">
                                                <label for="agree-checkbox">By clicking submit you are agreeing to our
                                                    <a href="javascript:void(0);"
                                                        onclick="openNewWindow(&#39;disclosures/terms.php&#39;,&#39;modal&#39;);">Terms
                                                        and Policies.</a> of this site</label>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer">
                <div class="container">

                    <p> *The statements made on our websites have not been evaluated by the FDA (U.S. Food &amp; Drug
                        Administration). Our products are not intended to diagnose, cure or prevent any disease. The
                        information provided by this website or this company is not a substitute for a face-to-face
                        consultation with your physician, and should not be construed as individual medical advice. The
                        testimonials on this website are individual cases and do not guarantee that you will get the
                        same
                        results. Due to the nature of this product and to protect the privacy of the individuals, actual
                        names and photographs of the individuals depicted in the testimonials have been changed.
                        Individuals are remunerated.
                    </p>
                    <p> Copyright &copy;
                        <script type="text/javascript">
                            var year = new Date();
                            document.write(year.getFullYear());
                        </script> <?php echo trim($configs->step_1_name); ?>- All Rights Reserved.
                    </p>
                    <ul class="terms-links">
                        <li>
                            <a href="javascript:void(0);"
                                onclick="openNewWindow(&#39;disclosures/privacy.php&#39;,&#39;modal&#39;);"
                                class="modal-popup ajax-popup-link">Privacy Policy |</a>
                        </li>
                        <li>
                            <a href="javascript:void(0);"
                                onclick="openNewWindow(&#39;disclosures/terms.php&#39;,&#39;modal&#39;);"
                                class="modal-popup ajax-popup-link">Terms and Conditions |</a>
                        </li>
                        <li>
                            <a href="javascript:void(0);"
                                onclick="openNewWindow(&#39;disclosures/wireless.php&#39;,&#39;modal&#39;);"
                                class="modal-popup ajax-popup-link">Wireless</a>
                        </li>
                    </ul>


                </div>
            </footer>
        </div>
    </div>

    <section class="custom-social-proof" style="display: none;">
        <div class="custom-notification" bis_skin_checked="1">
            <div class="custom-notification-container" bis_skin_checked="1">
                <div class="custom-notification-image-wrapper" bis_skin_checked="1">
                    <img src="assets/images/product.png">
                </div>
                <div class="custom-notification-content-wrapper" bis_skin_checked="1">
                    <p class="custom-notification-content">
                        <span id="notify-customer">Audrey A</span>. - <span id="notify-state">MN</span><br> Purchased
                        <strong><span id="notify-quantity">3</span></strong> Bottle(s) of
                        <?php echo $configs->step_1_name; ?> Male Enhancement Gummies <small><span id="notify-ago">21
                                minutes ago</span></small>
                    </p>
                </div>
            </div>
            <div class="custom-close" bis_skin_checked="1"></div>
        </div>
    </section>

    <div class="popup-loading-wrapper" style="display:none;" bis_skin_checked="1">
        <div class="popup" bis_skin_checked="1">
            <figure class="product-image"></figure>
            <p>Reserving Your Bottle Of</p>
            <h2><?php echo $configs->step_1_name; ?> Male Enhancement Gummies</h2>
            <img src="assets/images/icon-loading.png" alt="" class="loading-image">
        </div>
    </div>

    <div class="loading-screen" style="display:none;" bis_skin_checked="1">
        <div class="loading-pop" bis_skin_checked="1">
            <div class="pop-content" bis_skin_checked="1">
                <!-- <img src="/trtv2//assets/images/loading.gif" class="loading-img"> -->
                <ul class="pop-list">
                    <li class="show-1"><img src="assets/images/pop-tik.png"> Checking
                        Inventory
                    </li>
                    <li class="show-2"><img src="assets/images/pop-tik.png"> Processing
                        Transaction
                    </li>
                    <li class="show-3"><img src="assets/images/pop-tik.png"> Sending to
                        Fulfillment
                    </li>
                    <li class="show-4"><img src="assets/images/pop-tik.png"> Order Confirmed
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



    <script type="text/javascript" src="assets/js/jquery-3.5.1.min.js"></script>
    <script type="text/javascript" src="assets/js/micromodal.min.js"></script>
    <script type="text/javascript" src="assets/js/social-proof.js"></script>
    <script type="text/javascript" src="assets/js/scripts.js"></script>
    <script type="text/javascript" src="assets/js/jquery.mask.min.js"></script>
    <script type="text/javascript" src="assets/js/jquery.cardtype.js"></script>

    <script type="text/javascript" src="assets/js/card-validator.min.js"></script>
    <script type="text/javascript" src="assets/js/cc-validator.js"></script>

    <script type="text/javascript">
        $('form#checkout').find('#cardNumber').on('change keyup', function () {

            if ($.trim($("#cardNumber").val()) != '') {
                checkCardType($("#cardNumber").val());
            } else {
                $('#cc_type').val("");
            }
        });


        function checkCardType(e) {
            var result = $('#cardNumber').validateCreditCard();
            if (result && result.card_type.name) {
                var card = result.card_type.name;

                card = card.replace("mastercard", "master");

                $("input[name=fields_cc_type]").val(card);
            }

            /*  if (!$("#cardNumber").val() && !$.isNumeric($("#cardNumber").val())) {} else {
 
                 var firstChar = $("#cardNumber").val().substr(0, 1);
 
                 if (firstChar === "5") {
                     $('#cc_type').val("master");
 
                 } else if (firstChar === "4") {
                     $('#cc_type').val("visa");
                 } else if (firstChar === "6") {
 
                     $('#cc_type').val("");
                     alert('Unfortunately at this time we don`t accept Discover card, only VISA and MASTERCARD. Please use one of these payment methods to complete your purchase and start your Keto Diet!');
 
                     $("#cardNumber").val('');
 
                 } else if (firstChar === "1") {
                     $('#cc_type').val("visa");
                 } else {
 
                     $('#cc_type').val("");
 
                     if (typeof(e) !== 'undefined') {
 
                     }
 
                     $("#cardNumber").val('');
 
                 }
 
             } */

        }

        function GetCardType(number) {
            // visa
            var re = new RegExp("^4");
            if (number.match(re) != null)
                return "visa";

            // Mastercard 
            // Updated for Mastercard 2017 BINs expansion
            if (/^(5[1-5][0-9]{14}|2(22[1-9][0-9]{12}|2[3-9][0-9]{13}|[3-6][0-9]{14}|7[0-1][0-9]{13}|720[0-9]{12}))$/
                .test(number))
                return "master";

            // AMEX
            re = new RegExp("^3[47]");
            if (number.match(re) != null)
                return "amex";

            // Discover
            re = new RegExp("^(6011|622(12[6-9]|1[3-9][0-9]|[2-8][0-9]{2}|9[0-1][0-9]|92[0-5]|64[4-9])|65)");
            if (number.match(re) != null)
                return "discover";

            // Diners
            re = new RegExp("^36");
            if (number.match(re) != null)
                return "diners";

            // Diners - Carte Blanche
            re = new RegExp("^30[0-5]");
            if (number.match(re) != null)
                return "diners";

            // JCB
            re = new RegExp("^35(2[89]|[3-8][0-9])");
            if (number.match(re) != null)
                return "jcb";

            // Visa Electron
            re = new RegExp("^(4026|417500|4508|4844|491(3|7))");
            if (number.match(re) != null)
                return "none";

            return "";
        }

    </script>
    <script>
        // Iterate product selection
        var selectButtons = $('.package-info__btn');
        $(".product-selection > .product").click(function () {
            // Update package selected
            $('.active').removeClass("active");
            $(this).addClass("active");

            // Update hidden field
            $("input[name=package]").val($(this).data('pkg'));
            if ($(this).data('pkg') == 5) {
                // $('#custom_product').val(987);
            }
            if ($(this).data('pkg') == 3) {
                // $('#custom_product').val(985);
            }
            if ($(this).data('pkg') == 1) {
                // $('#custom_product').val(983);
            }
            // Update button text
            var parentElement = this;
            selectButtons.each(function (i) {
                if ($.contains(parentElement, this)) {
                    $(this).text("Selected!");
                } else {
                    $(this).text("Select Package");
                }
            });
        });
    </script>


    <script>
        var system_device_fingerprint = '';
        var default_merchant_id = '';
        var prospect_id = 6281187;
        var traffic_page_id = 59;
        var use_cc_validation = true;

        function submit_form() {
            if ((typeof gip_device_fingerprint != 'undefined') && gip_device_fingerprint) {
                $('#device_finger_print').val(gip_device_fingerprint);
            } else if (system_device_fingerprint) {
                $('#device_finger_print').val(system_device_fingerprint);
            }

            $("#proc_popup").show();

            var errors = new Array();

            // Validate shipping fields (always required)
            if ($('#shipping_fname').val() == '' || $('#shipping_fname').val().replace(/\s/g, "") == '') {
                errors.push('Please enter your First Name.');
            }
            if ($('#shipping_lname').val() == '' || $('#shipping_lname').val().replace(/\s/g, "") == '') {
                errors.push('Please enter your Last Name.');
            }
            if ($('#shipping_address1').val() == '' || $('#shipping_address1').val().replace(/\s/g, "") == '') {
                errors.push('Please enter your Shipping Address.');
            }
            if ($('#shipping_city').val() == '' || $('#shipping_city').val().replace(/\s/g, "") == '') {
                errors.push('Please enter your City.');
            }
            if ($('#shipping_state').val() == '' || $('#shipping_state').val().replace(/\s/g, "") == '') {
                errors.push('Please select your State.');
            }
            if ($('#shipping_zip').val() == '' || $('#shipping_zip').val().replace(/\s/g, "") == '') {
                errors.push('Please enter your Zip Code.');
            }
            if ($('#shipping_phone').val().replace(/\D/g, "").length != 10) {
                errors.push('Please enter a valid 10-digit Phone Number.');
            }
            var shippingEmailVal = $('#shipping_email').val();
            if (shippingEmailVal == '' || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(shippingEmailVal)) {
                errors.push('Please enter a valid Email Address.');
            }

            if (!$('#payment_as_shipping').prop('checked')) {
                // Validate billing fields
                if ($('#billing_street_address').val() == '' || $("#billing_street_address").val().replace(/\s/g, "") == '') {
                    errors.push('Please enter Billing Address.');
                }
                if ($('#billing_city').val() == '' || $("#billing_city").val().replace(/\s/g, "") == '') {
                    errors.push('Please enter Billing City.');
                }
                if ($('#billing_state').val() == '' || $("#billing_state").val().replace(/\s/g, "") == '') {
                    errors.push('Please select Billing State.');
                }
                if ($('#billing_postcode').val() == '' || $("#billing_postcode").val().replace(/\s/g, "") == '') {
                    errors.push('Please enter Billing Zip Code.');
                }
            }

            // Check CC #
            if ($("#cardNumber").val() === "") {
                errors.push("Please enter a credit card number.");
            } else {
                /* if (!validateCC($("#cardNumber").val())) {
                    errors.push("Incorrect Credit Card number. Please check and enter a valid Credit Card number.");
                    sendCcStats(traffic_page_id, prospect_id, 'incorrect_input');
                } else {
                    sendCcStats(traffic_page_id, prospect_id, 'corrected_input');
                } */
            }

            var empty_exp_date = false;
            if ($("#fields_expmonth").val() === "" || $("#fields_expmonth").val() === "Month") {
                empty_exp_date = true;
                errors.push("Please enter a valid expiration month.");
            }
            if ($("#fields_expyear").val() === "" || $("#fields_expyear").val() === "Year") {
                empty_exp_date = true;
                errors.push("Please enter a valid expiration year.");
            }
            if (!empty_exp_date && !checkCcExpDate($("#fields_expmonth").val(), $("#fields_expyear").val())) {
                errors.push("The selected date has already expired. Please enter a valid expiration date.");
            }
            if ($("#fields_cvv").val() === "") {
                errors.push("Please enter a CVV code.");
            }

            if (errors.length == 0) {
                $('.loading-screen .show-1, .loading-screen .show-2, .loading-screen .show-3, .loading-screen .show-4').removeClass("active");
                $('.loading-screen').show();
                setTimeout(function () {
                    $('.loading-screen .show-1').addClass("active");
                }, 500);
                setTimeout(function () {
                    $('.loading-screen .show-2').addClass("active");
                }, 2000);
                setTimeout(function () {
                    $('.loading-screen .show-3').addClass("active");
                }, 3000);

                var data = $("#checkout :input[value!=''][value!='.']").serialize();

                $.ajax({
                    type: "POST",
                    url: "./app/src/create-combined.php",
                    data: data,
                    success: function (result) {
                        var response = result.split("|");
                        response = response.map((value) => value.trim());
                        /*   if (response[0] == 'merchant') {
                              let merchantId = response[1];
                              if (typeof response[2] != 'undefined' && response[2]) {
                                  $('#gateway_id').val(response[2]);
                              }
                              if (typeof response[3] != 'undefined' && response[3]) {
                                  $('#shipping_id').val(response[3]);
                              }
                              if (merchantId) {
                                  if (typeof response[4] != 'undefined' && response[4]) {
                                      var checkoutURl = response[4];
                                      const iframe = document.createElement('iframe');
                                      iframe.src = checkoutURl + "?merchant_id=" + merchantId;
                                      iframe.width = "1";
                                      iframe.height = "1";
                                      iframe.frameborder = "0";
                                      document.body.appendChild(iframe);
  
                                      iframe.onload = function() {
                                          if (typeof system_device_fingerprint !== 'undefined' && system_device_fingerprint) {
                                              $('#device_finger_print').val(system_device_fingerprint);
                                              submit_form();
                                          } else {
                                              getReachFingerprintAndSubmit(merchantId);
                                          }
                                      };
                                  } else {
                                      getReachFingerprintAndSubmit(merchantId);
                                  }
                              } else {
                                  getReachFingerprintAndSubmit(default_merchant_id);
                              }
                              return;
                          } */

                        if (response[0] == "ok") {
                            window.location.href = "upsell.php" + response[1];
                        } else if (response[0] == 'oksc') {
                            window.location.href = 'upsell-sc.php' + response[1];
                        } else if (response[0] == 'okprepaid') {
                            window.location.href = 'upsell-pp.php' + response[1];
                        } else {
                            $(".loading-screen").hide();
                            /*   if (['AVS REJECTED'].includes(response[0])) {
  
                                  alert(response[0] + '\n' + 'Your Billing Address Is Different From Your Shipping Address. Please click ok and enter your correct billing address and submit the order again.')
                                  $('#payment_as_shipping').prop('checked', true)
                                  $('#payment_as_shipping').trigger('click')
                              } else { */
                            popErrorModal(response[1] || response[0]);
                            //}
                        }
                    },
                    error: function () {
                        $(".loading-screen").hide();
                    }
                });

                $('#cardNumber').mask("0000 0000 0000 0000");

                return false;

            } else {

                $('#cardNumber').mask("0000 0000 0000 0000");

                var li = '';

                $.each(errors, function (key, value) {
                    li += '<li' + ((errors.length == 1) ? ' class="center"' : '') + '>' + value + '</li>';
                });
                var html = '';
                /*html += '<div id="error_handler_overlay">';

                html += '<div class="error_handler_body"><a href="javascript:void(0);" id="error_handler_overlay_close">X</a><ul>' + li + '</ul></div>';
                html += '</div>';*/
                html += getDeclineModal(li);

                $('body').append(html);
                $("#proc_popup").hide();

                $('#error_handler_overlay').fadeIn(500);
                return false;
            }

            return false;
        }

        function getReachFingerprintAndSubmit(merchantId) {
            var script = document.createElement('script');
            script.async = true;
            script.src = "https://checkout.rch.io/v2.22/fingerprint?MerchantId=" + merchantId;
            document.head.appendChild(script);

            script.onload = function () {
                if (typeof gip_device_fingerprint !== 'undefined' && gip_device_fingerprint) {
                    $('#device_finger_print').val(gip_device_fingerprint);
                }
                submit_form();
            }
        }

        function popErrorModal(errors) {

            var error_title = '';
            var li = '';

            if (errors == "Order is already completed") {
                li = '<li>' + errors + '</li>';
            } else if (typeof errors === 'string') {
                if (errors[0] == '#') {
                    errors = errors.substring(1);
                    var errors_array = errors.split('.');
                    error_title = errors_array[0] + '.';
                    var errors_list = errors.substring(error_title.length);
                    li = '<li class="center">' + errors_list + '</li>';
                } else {
                    li = '<li class="center">' + errors + '</li>';
                }
            } else {
                $.each(errors, function (key, value) {
                    li += '<li' + ((errors.length == 1) ? ' class="center"' : '') + '>' + value + '</li>';
                });
            }

            var html = '';
            /*html += '<div id="error_handler_overlay">';
            html += '<div class="error_handler_body"><a href="javascript:void(0);" id="error_handler_overlay_close">X</a><ul>' + li + '</ul></div>';
            html += '</div>';*/
            html += getDeclineModal(li, error_title);

            $('body').append(html);
            $("#proc_popup").hide();

            $('#error_handler_overlay').fadeIn(500);
            return false;
        }

        function getDeclineModal(errors_list, error_title) {
            var html = '';
            html += '<div class="modal micromodal-slide modal-decline" id="error_handler_overlay">';
            html += '    <div class="modal__overlay" tabindex="-1" data-modal-overlay-close="modal-decline">';
            html += '        <div class="modal__container modal__content" role="dialog" aria-modal="true">';
            html += '            <header class="modal__header">';
            html += '                <h2 class="modal__title">';
            html += '                    <span></span>';
            html += '                    <button type="button" class="modal__close" aria-label="Close modal" data-modal-close></button>';
            html += '                </h2>';
            html += '            </header>';
            html += '            <div class="modal__body">';
            html += '                <div style="text-align:center;"><img src="assets/images/warning.png" width="50" height="50"></div>';
            html += '                <h2>' + ((typeof error_title != 'undefined' && error_title) ? error_title : 'Warning!') + '</h2>';
            html += '            </div>';
            html += '            <div class="modal__footer">';
            html += '                <ul>' + errors_list + '</ul>';
            html += '            </div>';
            html += '            <div class="modal__button">';
            html += '                <button data-modal-close>Got It</button>';
            html += '            </div>';
            html += '        </div>';
            html += '    </div>';
            html += '</div>';
            return html;
        }


        function LuhnAlgorithm(num) {
            return true;
        };
    </script>


    <script>
        jQuery(function ($) {
            $(".cvv-link").click(function () {
                $('.cvv-image').show();
            });

            /* $("#cardNumber").blur(function() {
                var ccLength = $("#cardNumber").val().replace(/\s+/g, "").length;
                if (ccLength >= 12) {
                    if (!validateCC($("#cardNumber").val())) {
                        $('#cc_number_label').addClass('cc-validation-error');
                        $('#cardNumber').addClass('cc-input-validation-error');
                        $('.cc-text-validation-error').show();
                    } else {
                        $('#cc_number_label').removeClass('cc-validation-error');
                        $('#cardNumber').removeClass('cc-input-validation-error');
                        $('.cc-text-validation-error').hide();
                    }
                } else {
                    $('#cc_number_label').removeClass('cc-validation-error');
                    $('#cardNumber').removeClass('cc-input-validation-error');
                    $('.cc-text-validation-error').hide();
                }
            }); */

            $("#cardNumber").on("keyup change", function () {

    var number = $(this).val().replace(/\s+/g, "");

    if (number.length === 16) {

        var result = $(this).validateCreditCard();

        if (!result.valid) {

            $('#cc_number_label').addClass('cc-validation-error');
            $('#cardNumber').addClass('cc-input-validation-error');
            $('.cc-text-validation-error').show();

        } else {

            $('#cc_number_label').removeClass('cc-validation-error');
            $('#cardNumber').removeClass('cc-input-validation-error');
            $('.cc-text-validation-error').hide();
        }

    } else {

        $('#cc_number_label').removeClass('cc-validation-error');
        $('#cardNumber').removeClass('cc-input-validation-error');
        $('.cc-text-validation-error').hide();
    }
});
        });

        $(window).on('load', function () {
            var $preloader = $('#preloader');
            var $preloader2 = $('#popup-loading-wrapper2');
            $preloader.delay(500).fadeOut('slow');
            $preloader2.delay(500).fadeOut('slow');
        });

        window.addEventListener('message', (event) => {
            if (typeof event.data !== 'undefined' && event.data &&
                (typeof event.data.type != 'undefined') && (event.data.type === 'get_fingerprint') &&
                (typeof event.data.value != 'undefined') && event.data.value) {
                system_device_fingerprint = event.data.value;
            }
        });

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

        $(document).ready(function () {
            $("#cardNumber").mask("0000 0000 0000 0000");
            $("#shipping_phone").mask("000-000-0000");
        });
    </script>

    <script>
        $('#payment_as_shipping').click(function () {
            console.log(this.checked)
            if (this.checked) {
                $('.billing-info').hide();
                $('#billingSameAsShipping').val("1");
            } else {
                $('.billing-info').show();
                $('#billingSameAsShipping').val("0");
            }
        });
    </script>

    <script>
        function onlyNumbers(e, type) {
            var keynum;
            var keychar;
            var numcheck;
            if (window.event) // IE
            {
                keynum = e.keyCode;
            } else if (e.which) // Netscape/Firefox/Opera
            {
                keynum = e.which;
            }
            keychar = String.fromCharCode(keynum);
            numcheck = /\d/;

            switch (keynum) {
                case 8: //backspace
                case 9: //tab
                case 35: //end
                case 36: //home
                case 37: //left arrow
                case 38: //right arrow
                case 39: //insert
                case 45: //delete
                case 46: //0
                case 48: //1
                case 49: //2
                case 50: //3
                case 51: //4
                case 52: //5
                case 54: //6
                case 55: //7
                case 56: //8
                case 57: //9
                case 96: //0
                case 97: //1
                case 98: //2
                case 99: //3
                case 100: //4
                case 101: //5
                case 102: //6
                case 103: //7
                case 104: //8
                case 105: //9
                    result2 = true;
                    break;
                case 109: // dash -
                    if (type == 'phone') {
                        result2 = true;
                    } else {
                        result2 = false;
                    }
                    break;
                default:
                    result2 = numcheck.test(keychar);
                    break;
            }

            return result2;
        }

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
    </script>

    <script type="text/javascript">
        function GetCardType(number) {
            // visa
            var re = new RegExp("^4");
            if (number.match(re) != null)
                return "visa";

            // Mastercard
            // Updated for Mastercard 2017 BINs expansion
            if (/^(5[1-5][0-9]{14}|2(22[1-9][0-9]{12}|2[3-9][0-9]{13}|[3-6][0-9]{14}|7[0-1][0-9]{13}|720[0-9]{12}))$/.test(number))
                return "master";

            // AMEX
            re = new RegExp("^3[47]");
            if (number.match(re) != null)
                return "amex";

            // Discover
            re = new RegExp("^(6011|622(12[6-9]|1[3-9][0-9]|[2-8][0-9]{2}|9[0-1][0-9]|92[0-5]|64[4-9])|65)");
            if (number.match(re) != null)
                return "discover";

            // Diners
            re = new RegExp("^36");
            if (number.match(re) != null)
                return "diners";

            // Diners - Carte Blanche
            re = new RegExp("^30[0-5]");
            if (number.match(re) != null)
                return "diners";

            // JCB
            re = new RegExp("^35(2[89]|[3-8][0-9])");
            if (number.match(re) != null)
                return "jcb";

            // Visa Electron
            re = new RegExp("^(4026|417500|4508|4844|491(3|7))");
            if (number.match(re) != null)
                return "none";

            return "";
        }


        function protectInput(field) {
            let typed = false;
            let iscreditCardFillupByPaste = false;

            field.addEventListener("keydown", () => typed = true);

            field.addEventListener("change", () => {
                if (this.value != "" && (!typed || iscreditCardFillupByPaste)) {
                    $('#iscreditcardPasted').val(1);
                    console.log('pasted on ')
                }
            });
            field.addEventListener("paste", () => iscreditCardFillupByPaste = true);
        }

        document.querySelectorAll("#cardNumber").forEach(protectInput);
    </script>


    <script type="text/javascript" src="assets/js/card_discount.js"></script>
    
<!-- Mastercard $20 Discount Side Banner -->
<div id="mcDiscountBanner" class="mc-discount-banner">

    <button
        type="button"
        id="mcBannerToggle"
        class="mc-banner-toggle"
        aria-label="Minimize discount banner"
        aria-expanded="true">
        &#10095;
    </button>

    <div class="mc-banner-content">

        <div class="mc-brand">
            <span class="mc-circle mc-red"></span>
            <span class="mc-circle mc-orange"></span>
        </div>

        <div class="mc-brand-name">mastercard</div>

        <div class="mc-divider"></div>

        <div class="mc-offer-heading">SPECIAL OFFER</div>

        <div class="mc-pay-text">PAY WITH</div>
        <div class="mc-card-name">MASTERCARD</div>

        <div class="mc-and-get">AND GET</div>
        <div class="mc-discount-amount">$20</div>
        <div class="mc-off-text">OFF</div>
        <div class="mc-order-text">ON YOUR ORDER</div>

        <div class="mc-divider"></div>

        <div class="mc-exclusive">
            <span class="mc-crown">&#9813;</span>
            EXCLUSIVE DISCOUNT
        </div>

        <div class="mc-banner-small">
            Discount applies when an eligible Mastercard is used.
        </div>

    </div>
</div>

<style>
.mc-discount-banner {
    position: fixed;
    top: 50%;
    right: 0;
    transform: translateY(-50%);

    width: 190px;
    height: 80vh;
    height: 80dvh;
    max-height: 650px;
    min-height: 400px;

    box-sizing: border-box;
    z-index: 99999;
    overflow: visible;

    background: linear-gradient(
        180deg,
        #fffdf3 0%,
        #f7f3df 100%
    );

    color: #064b36;
    border-left: 3px solid #c9a64d;
    box-shadow: -3px 0 15px rgba(0, 0, 0, 0.12);
    font-family: Arial, Helvetica, sans-serif;

    transition: transform .3s ease;
}

.mc-banner-content {
    width: 100%;
    height: 100%;
    box-sizing: border-box;
    overflow-y: auto;

    padding: 20px 13px;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.mc-banner-toggle {
    position: absolute;
    top: 50%;
    left: -24px;
    transform: translateY(-50%);

    width: 25px;
    height: 54px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #07543d;
    color: #fff;
    border: 0;
    border-radius: 10px 0 0 10px;

    font-size: 21px;
    font-weight: bold;
    cursor: pointer;

    box-shadow: -2px 2px 7px rgba(0, 0, 0, .14);
}

.mc-brand {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100px;
    height: 43px;
    margin-bottom: 5px;
    flex-shrink: 0;
}

.mc-circle {
    display: block;
    width: 43px;
    height: 43px;
    border-radius: 50%;
}

.mc-red {
    background: #eb001b;
    margin-right: -12px;
}

.mc-orange {
    background: #f79e1b;
}

.mc-brand-name {
    font-size: 19px;
    font-weight: 700;
    margin-bottom: 18px;
    letter-spacing: -.5px;
}

.mc-divider {
    width: 82%;
    height: 2px;
    flex-shrink: 0;
    background: #c9a64d;
    margin: 0 auto 16px;
}

.mc-offer-heading {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.7px;
    margin-bottom: 16px;
}

.mc-pay-text {
    font-size: 16px;
    margin-bottom: 5px;
}

.mc-card-name {
    font-size: 18px;
    font-weight: 900;
    line-height: 1.3;
}

.mc-and-get {
    font-size: 15px;
    margin-top: 15px;
}

.mc-discount-amount {
    color: #07543d;
    font-size: clamp(44px, 5vw, 64px);
    line-height: 1;
    font-weight: 900;
    letter-spacing: -2px;
    margin-top: 5px;
}

.mc-off-text {
    color: #c49b3e;
    font-size: 38px;
    line-height: 1.1;
    font-weight: 900;
}

.mc-order-text {
    font-size: 12px;
    margin: 10px 0 18px;
}

.mc-exclusive {
    font-size: 12px;
    font-weight: 900;
    letter-spacing: .6px;
    line-height: 1.6;
}

.mc-crown {
    display: block;
    font-size: 25px;
    color: #c49b3e;
    margin-bottom: 5px;
}

.mc-banner-small {
    font-size: 10px;
    line-height: 1.5;
    color: #385c4e;
    margin-top: 12px;
}

/* Minimize banner; keep the arrow visible */
.mc-discount-banner.mc-collapsed {
    transform: translate(100%, -50%);
    box-shadow: none;
}

.mc-discount-banner.mc-collapsed .mc-banner-toggle {
    left: -30px;
    width: 32px;
}

/* Short laptop screens */
@media (max-height: 650px) {
    .mc-discount-banner {
        height: 86vh;
        height: 86dvh;
        min-height: 0;
    }

    .mc-banner-content {
        justify-content: flex-start;
        padding-top: 12px;
        padding-bottom: 12px;
    }

    .mc-brand-name {
        margin-bottom: 8px;
    }

    .mc-divider {
        margin-bottom: 9px;
    }

    .mc-offer-heading {
        margin-bottom: 8px;
    }

    .mc-and-get {
        margin-top: 8px;
    }

    .mc-order-text {
        margin-bottom: 10px;
    }

    .mc-banner-small {
        margin-top: 8px;
    }
}

/* Mobile: keep checkout unobstructed */
@media (max-width: 767px) {
    .mc-discount-banner {
        display: none;
    }
}
</style>

<script>
(function () {
    var banner = document.getElementById('mcDiscountBanner');
    var toggle = document.getElementById('mcBannerToggle');

    if (!banner || !toggle) return;

    toggle.addEventListener('click', function () {
        var collapsed = banner.classList.toggle('mc-collapsed');

        toggle.innerHTML = collapsed ? '&#10094;' : '&#10095;';

        toggle.setAttribute(
            'aria-expanded',
            String(!collapsed)
        );

        toggle.setAttribute(
            'aria-label',
            collapsed
                ? 'Expand discount banner'
                : 'Minimize discount banner'
        );
    });
})();
</script>
</body>

</body>

</html>