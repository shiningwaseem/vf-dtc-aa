<?php

	include('../_global.php');
	$notes = "";


	if(!isset($notes)) {
		$notes = "";
	}

	if(empty($_SESSION['sessionId'])){
		$_SESSION['sessionId'] = '';
	}

	$nextPage = 'shipping.php?' . (!empty($_SERVER['QUERY_STRING']) ? ( $_SERVER['QUERY_STRING']) : 'qs=');

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?php echo $configs->step1_name;?></title>
<meta content="width=device-width, initial-scale=1" name="viewport">
<link href="<?php echo $configs->root_path?>/assets/brand/favicon.png" rel="icon" type="image/png">

<link href="<?php echo $configs->root_path . "mobile/assets/css/app.css"; ?>" rel="stylesheet">
<link href="<?php echo $configs->root_path . "mobile/assets/css/inner.css"; ?>" type="text/css" rel="stylesheet">
<link href="<?php echo $configs->root_path . "mobile/assets/css/btn-animation.css";?>" type="text/css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Oswald&display=swap" rel="stylesheet">
<script>
	history.pushState(null, null, window.location.href);
	history.back();
	history.forward(); 
	window.onpopstate = function () { history.go(1); };
</script>
	
</head>

<body style="max-width: 600px; margin: 0 auto">
<div id="app">
    <div class="choose-p" id="pagecontainer">
    <div id="top-1">
        <center>
            <img src="<?php echo $configs->root_path . "mobile/assets/brand/logo.png";?>" style="width:158px; margin: 6px 0 10px 0;">
        </center>
        <div class="shipping-container">
            <span><strong>Fast, Free Shipping</strong> For A Limited Time</span>
        </div>
        <div class="breadcrumbs">
            <ul class="breadcrumbs__list">
                <li class="breadcrumbs__item ">
                    <span>Qualify Now</span>
                </li>
                <li class="breadcrumbs__item breadcrumbs__item_2 breadcrumbs__item_active">
                    <span>Select Package</span>
                </li>
                <li class="breadcrumbs__item breadcrumbs__item_3">
                    <span>Confirm Order</span>
                </li>
            </ul>
        </div>

        <p style="text-align:center; padding-top: 13px;">
            <b style="color:#000; font-size:18px;">
                <span style="color:#80399F;">Approved</span>
            </b>
        </p>
    </div>
    <div class="linebreak" style="margin:11px 0 13px;"></div>
    <!-- 5 bottle -->
    <div class="package package1" data-order-type="1">
        <div class="package__left">
            <span class="package__name">Package 1</span>
            <span class="package__coll">3 BOTTLES</span>
            <span class="package__free">+ Get 2 Free!*</span>
            <span class="package__some">same as</span>
            <span class="package__price"><p style="display: inline-block" class="price">$<?php echo $configs->bottle_per_5; ?></p><span>/bottle</span></span>
            <span class="package__stock">In Stock - Sell Out Risk: High</span>
            <span class="save-label">save over <span>40%</span></span>
        </div>
        <div class="package__right">
            <div class="bottle-block clearfix">
                <div class="package-images__item"> 
                    <img alt="" src="<?php echo $configs->root_path ?>assets/brand/product.png" class="prod-btl2">
                    <img alt="" src="<?php echo $configs->root_path ?>assets/brand/product.png" class="prod-btl3" style="left:19%;">
                    <img alt="" src="<?php echo $configs->root_path ?>assets/brand/product.png" class="prod-btl3">
                </div>
                <img alt="" src="<?php echo $configs->root_path ?>assets/images/plus-icon.png" class="chk-plus">
                <div class="package-images__item">
                    <img alt="" src="<?php echo $configs->root_path ?>assets/brand/product.png" class="prod-btl4">
                    <!-- <img alt="" src="<?php echo $configs->root_path ?>assets/brand/product.png" class="prod-btl6"> -->
                    <img alt="" src="<?php echo $configs->root_path ?>assets/brand/product.png" class="prod-btl5">
                </div>
            </div>
            <div class="retail-block">
                <span class="package__retail">Retail: <span class="retail-price">$<?php echo $configs->bottle_retail_5; ?></span>/bottle</span>
                <span class="shipping-block"><span>Free shipping</span></span>
            </div>
        </div>
        <span class="best-choise">Best <br> choice</span>
        <a href="<?php echo $nextPage; ?>&pk=5"><span class="package-btn btn_pulse">yes - choose this package <span>»</span></span></a>
    </div>

    <div class="linebreak" style="margin:13px 0 12px;"></div>

    <div class="package package2" data-order-type="2">
        <div class="package__left">
            <span class="package__name">Package 2</span>
            <span class="package__coll">2 BOTTLES</span>
            <span class="package__free">+ Get 1 Free!*</span>
            <span class="package__some">same as</span>
            <span class="package__price"><p style="display: inline-block" class="price">$<?php echo $configs->bottle_per_3; ?></p><span>/bottle</span></span>
            <span class="package__stock">In Stock - Sell Out Risk: High</span>
        </div>
        <div class="package__right">
            <div class="bottle-block clearfix">
                <div class="package-images__item">
                    <img alt="" src="<?php echo $configs->root_path ?>assets/brand/product.png" class="prod-btl4" style="left:48%;">
                    <img alt="" src="<?php echo $configs->root_path ?>assets/brand/product.png" class="prod-btl3" style="left:28%;">
                </div>
                <img alt="" src="./assets/images/plus-icon.png" class="chk-plus">
                <div class="package-images__item">
                    <img alt="" src="<?php echo $configs->root_path ?>assets/brand/product.png" class="prod-btl4">
                    <!-- <img alt="" src="<?php echo $configs->root_path ?>assets/brand/product.png" class="prod-btl5"> -->
                </div>
                <div class="save-block">
                    <span class="save-block__title">Save</span>
                    <span class="save-block__price save-price">$<?php echo $configs->bottle_save_3; ?></span>
                </div>
            </div>
            <div class="retail-block">
                <span class="package__retail">Retail: <span class="retail-price">$<?php echo $configs->bottle_retail_3; ?></span>/bottle</span>
                <span class="shipping-block"><span>Free shipping</span></span>
            </div>
        </div>

        <a href="<?php echo $nextPage; ?>&pk=3"><span class="package-btn">yes - choose this package <span>»</span></span></a>
    </div>

    <div class="linebreak" style="margin:13px 0 12px;"></div>
    	<div class="package package3" data-order-type="3">
            <div class="package__left">
                <span class="package__name">Package 3</span>
                <span class="package__coll">1 BOTTLE</span>
		            <span class="package__free">+ Get 1 Free!*</span>
		            <span class="package__some">same as</span>
                <span class="package__some">&nbsp;</span>
                <span class="package__some">&nbsp;</span>
                <span class="package__price"><p style="display: inline-block" class="price">$<?php echo $configs->bottle_per_1;?></p><span>/bottle</span></span>
                <span class="package__stock">In Stock - Sell Out Risk: High</span>
            </div>
            <div class="package__right">
                <div class="bottle-block clearfix">
	                <div class="package-images__item">
	                    <img alt="" src="<?php echo $configs->root_path ?>assets/brand/product.png" class="prod-btl5" style="left:48%;">
	                </div>
	                <img alt="" src="<?php echo $configs->root_path ?>assets/images/plus-icon.png" class="chk-plus">
	                <div class="package-images__item">
	                    <img alt="" src="<?php echo $configs->root_path ?>assets/brand/product.png" class="prod-btl5">
	                </div>
                </div>
                <div class="retail-block">
                    <span class="package__retail ">Retail: <span class="retail-price">$<?php echo $configs->bottle_retail_1; ?></span>/bottle</span>
                    <span class="shipping-block"><span>FREE SHIPPING</span></span>
                </div>
            </div>

        	<a href="<?php echo $nextPage; ?>&pk=1"><span class="package-btn">yes - choose this package <span>»</span></span></a>
    	</div>

    	<span class="footnote">* at retail price</span>

        <div class="linebreak" style="margin:9px 0 12px;"></div>
    </div>
    <div id="top-2">
        <img src="./assets/images/godaddyimg.png">
    </div>
    <div class="linebreak" style="margin:13px 0 12px;"></div>
    <div id="top-3">
        <img src="./assets/images/safe.png">
    </div>
    <div class="linebreak" style="margin:12px 0 7px;"></div>

    <div id="footer">
        <br>
        <center>
            <ul class="terms-links">
                <li> <a href="javascript:void(0);" onClick="openNewWindow('../disclosures/privacy.php','modal');">Privacy Policy |</a></li>
                <li> <a href="javascript:void(0);" onClick="openNewWindow('../disclosures/terms.php','modal');">Terms and Conditions |</a></li>
                <li><a href="javascript:void(0);" onClick="openNewWindow('../disclosures/wireless.php','modal');">Wireless Policy</a></li>
								
            </ul>
            <br>
            <div class="text-center">
                <p><?php echo $configs->step1_name;?> is committed to maintaining the highest quality products and the utmost integrity in business practices. All products sold on this website are certified by Good Manufacturing Practices (GMP), which is the highest standard of testing in the supplement industry.</p><br><p>Notice: The products and information found on this site are not intended to replace professional medical advice or treatment. These statements have not been evaluated by the Food and Drug Administration. These products are not intended to diagnose, treat, cure or prevent any disease. Individual results may vary.</p>
            </div>
            <br>
        </center>

        <center>
            <div class="text-center">
                <p class="cop-text">&copy; <script> var date = new Date(); document.write(date.getFullYear()) </script> <span class="product-name"><?php echo $configs->corporate_name;?></span>. All Rights Reserved.</p>
                <br>
            </div>
        </center>
        <p></p>
    </div>
</div>

<script type="text/javascript" src="<?php echo $configs->root_path; ?>assets/js/jquery-3.5.1.min.js"></script>

<script type="text/javascript">
$(function() {
	$(document).on('click', '#app_common_modal_close', function() {
		//     alert('close');
		$('#app_common_modal').remove();
	});	
});

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

</body>
</html>