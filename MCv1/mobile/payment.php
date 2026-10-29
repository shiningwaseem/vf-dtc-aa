<?php

	include('../_global.php');

	if(!empty($_REQUEST['pk'])) {
		if($_REQUEST['pk']==1){
			$package = 1;
			//$productId = $configs->package_1_id;
		} else if($_REQUEST['pk']==3){
			$package = 3;
			//$productId = $configs->package_3_id;
		} else if($_REQUEST['pk']==5){
			$package = 5;
			//$productId = $configs->package_5_id;
		}
		$_SESSION["package"] = $package;
	}

	if(empty($package)) {
		$homeUrl = '/promo/mobile/choose.php?' . (!empty($_SERVER['QUERY_STRING']) ? ('?' . $_SERVER['QUERY_STRING']) : '');
		// header('Location:' . $homeUrl);
		// exit(0);
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title><?php echo $configs->step1_name;?></title>
	<meta content="width=device-width, initial-scale=1" name="viewport">
	<link href="<?php echo $configs->root_path . "/assets/brand/favicon.png"; ?>" rel="icon" type="image/png">

	<link href="<?php echo $configs->mobile_path . "/assets/css/app.css"; ?>" rel="stylesheet">
	<link href="<?php echo $configs->mobile_path ?>assets/css/inner.css" type="text/css" rel="stylesheet">
	<link href="<?php echo $configs->mobile_path; ?>/assets/css/btn-animation.css" type="text/css" rel="stylesheet">
	<link href="<?php echo rtrim($configs->root_path, '/') . "/assets/css/mc_promo.css"; ?>" rel="stylesheet">
	<style>
	.chkbox{
		width:auto !important;
		height:auto !important;
		appearance:checkbox !important;
		outline:auto !important;
		display:inline !important;
		text-align:center !important;
	}
	.frmCheckElemts {
		margin-top: 15px;
		text-align:center;
	}
	.frmCheckElemts label{
		font-size:12px !important;
		font-weight:normal;
	}

	form{padding:0;}
	</style>
	<script>
		history.pushState(null, null, window.location.href);
		history.back();
		history.forward(); 
		window.onpopstate = function () { history.go(1); };
	</script>
		
</head>
<body data-mobile-id="5984" style="max-width: 600px; margin: 0 auto">

<div id="app">
   <div class="checkout-page" id="pagecontainer">

      <div id="top-1">
            <center><img src="<?php echo $configs->mobile_path; ?>assets/brand/logo.png"
                    style="width:158px; margin: 6px 0 10px 0;"></center>
            <div class="breadcrumbs">
                <ul class="breadcrumbs__list">
                    <li class="breadcrumbs__item ">
                        <span>Qualify Now</span>
                    </li>
                    <li class="breadcrumbs__item breadcrumbs__item_2 ">
                        <span>Select Package</span>
                    </li>
                    <li class="breadcrumbs__item breadcrumbs__item_3 breadcrumbs__item_active">
                        <span>Confirm Order</span>
                    </li>
                </ul>
            </div>

            <p style="text-align:center;"><br>
                <b style="color:#000; font-size:18px;">Final Step - Payment Information</b><br>
                <span style="color:#444; font-size:11px;">
                    <em>Your order will be processed on our secure servers</em>
                </span>
                </p>
            <?php include(__DIR__ . '/../app/src/mc_banner.php'); ?>

            <div class="cards-list">
                <span>We Accept:</span>
                <img alt="" src="./assets/images/visa.png">
                <img alt="" src="./assets/images/mastercard.png">
            </div>


            <div id="shortcheckoutcontainer" style="max-width:95%; margin:0 auto;">
							<form class="paymentform form"  method="post"  onSubmit="return submit_lead();" id="order_form" name="order_form">
								
									<input type='hidden' name='token' value='<?php echo $_SESSION["token"]; ?>'>
								<input type='hidden' name='AFFID' value='<?php echo $_SESSION["affid"]; ?>'>
						      <input type="hidden" name="package" value="<?php echo (isset($_SESSION["package"])) ? $_SESSION["package"] : 5; ?>" id="package">
									<input type="hidden" name="fields_prospect_id" value="<?php echo isset($_GET["prospectId"])?$_GET["prospectId"]:''; ?>">
									<input type="hidden" name="fields_cc_type" value="">
									<input type="hidden" name="device" value="mobile">
									<input type="hidden" name="iscreditcardPasted" id="iscreditcardPasted" value="0">
									<input type="hidden" id="cc_type" name="cc_type" value="">
									
									<?php
									$amount = $configs->bottle_price_5;
									if (isset($_SESSION["package"]) && $_SESSION["package"] == 3) {
										$amount = $configs->bottle_price_3;
									}
						
									if (isset($_SESSION["package"]) && $_SESSION["package"] == 1) {
										$amount = $configs->bottle_price_1;// + $configs->bottle_ship_price_1;
									}
									?>
									<input type="hidden" id="amount" value="<?php echo $amount; ?>">
                    <div id="mc-price-discount" role="status" aria-live="polite" style="display:none;margin:12px 0;padding:12px;border:1px solid #b7dfc5;border-radius:8px;background:#f0fff4;color:#14532d;font-weight:600;">
                        Mastercard discount: -$<?= number_format((float) ($configs->mc_promo->price_discount_amount ?? 20), 2); ?>. Your discounted total: <span id="mc-price-discount-total"></span>
                    </div>
    
			            <?php
				            foreach($_GET as $key => $value) {
				            	echo "<input type='hidden' name='".safeRequest($key)."' value='".safeRequest($_GET[$key])."'>";
				            }
			            ?>
									
									<!-- 3DS -->
									<input type="hidden" name="cavv" id="cavv" value="">
									<input type="hidden" name="ds_trans_id" id="ds_trans_id" value="">
									<input type="hidden" name="eci" id="eci" value="">
									<input type="hidden" name="acs_trans_id" id="acs_trans_id" value="">
									<input type="hidden" name="3d_version" id="3d_version" value="">
						
									<!-- 3DS Rebill -->
									<input type="hidden" name="rebill_cavv" id="rebill_cavv" value="">
									<input type="hidden" name="rebill_ds_trans_id" id="rebill_ds_trans_id" value="">
									<input type="hidden" name="rebill_eci" id="rebill_eci" value="">
									<input type="hidden" name="rebill_acs_trans_id" id="rebill_acs_trans_id" value="">
									<input type="hidden" name="rebill_3d_version" id="rebill_3d_version" value="">
                    
                	<label for="payment_as_shipping" class="payment_as_shipping_label">
                        <input type="checkbox" id="payment_as_shipping" name="billing_is_shipping" checked class="bill-inp" value="no">
                        <span>Billing same as Shipping</span>
                	</label>
                    <div class="billing-info" style="display: none; margin-bottom: 15px">

                   
                    <div class="billing-form">
                        <div class="billing-title">Billing Information</div>

                        <div class="form-holder">
                            <span>First Name: </span>
                            <input class="form-control required" data-placement="auto left" name="fields_billing_fname" id="firstName" placeholder="First Name*" title="First Name" type="text">
                        </div>

                        <div class="form-holder" placeholder="Last Name*">
                            <span>Last Name: </span>
                            <input class="form-control required" data-placement="auto left" name="fields_billing_lname" id="lastName" placeholder="Last Name*" title="Last Name" type="text">
                        </div>

                        <div class=" form-holder">
                            <label>Address:</label>
                            <input class="form-control required" data-placement="auto left" name="fields_billing_address1" id="address1" placeholder="Address*" title="Address" type="text">
                        </div>

                        <div class=" form-holder">
                            <label>Zip Code:</label>
                            <input class="form-control required" data-placement="auto left" name="fields_billing_zip" id="postCode" placeholder="Zip Code*"  title="Zip Code" type="text" onKeyUp="javascript: this.value = this.value.replace(/[^0-9]/g, '');" >
                        </div>

                        <div class="form-holder">
                            <label>City:</label>
                            <input class="form-control required" data-placement="auto left" name="fields_billing_city" id="city" placeholder="City*" title="City" type="text">
                        </div>

                        <div class="form-holder" style="display:none;">
                            <label>Country:</label>
                            <select class="form-control" id="id_country" >
                            </select>
                        </div>

                        <div class=" form-holder">
                            <label>State/Region:</label>
                            <select  name="fields_billing_state" id="state" class="form-control required" />
			                        <option value="" onClick="" selected>Select Billing State</option>
			                        <option value="AL">Alabama (AL)</option>
															<option value="AK">Alaska (AK)</option>
															<option value="AS">American Samoa (AS)</option>
															<option value="AZ">Arizona (AZ)</option>
															<option value="AR">Arkansas (AR)</option>
															<option value="AE">Armed Forces (AE)</option>
															<option value="AA">Armed Forces Americas (AA)</option>
															<option value="AP">Armed Forces Pacific (AP)</option>
															<option value="CA">California (CA)</option>
															<option value="CO">Colorado (CO)</option>
															<option value="CT">Connecticut (CT)</option>
															<option value="DE">Delaware (DE)</option>
															<option value="DC">District of Columbia (DC)</option>
															<option value="FM">Federated States of Micronesia (FM)</option>
															<option value="FL">Florida (FL)</option>
															<option value="GA">Georgia (GA)</option>
															<option value="GU">Guam (GU)</option>
															<option value="HI">Hawaii (HI)</option>
															<option value="ID">Idaho (ID)</option>
															<option value="IL">Illinois (IL)</option>
															<option value="IN">Indiana (IN)</option>
															<option value="IA">Iowa (IA)</option>
															<option value="KS">Kansas (KS)</option>
															<option value="KY">Kentucky (KY)</option>
															<option value="LA">Louisiana (LA)</option>
															<option value="ME">Maine (ME)</option>
															<option value="MD">Maryland (MD)</option>
															<option value="MA">Massachusetts (MA)</option>
															<option value="MI">Michigan (MI)</option>
															<option value="MN">Minnesota (MN)</option>
															<option value="MS">Mississippi (MS)</option>
															<option value="MO">Missouri (MO)</option>
															<option value="MT">Montana (MT)</option>
															<option value="NE">Nebraska (NE)</option>
															<option value="NV">Nevada (NV)</option>
															<option value="NH">New Hampshire (NH)</option>
															<option value="NJ">New Jersey (NJ)</option>
															<option value="NM">New Mexico (NM)</option>
															<option value="NY">New York (NY)</option>
															<option value="NC">North Carolina (NC)</option>
															<option value="ND">North Dakota (ND)</option>
															<option value="MP">Northern Mariana Islands (MP)</option>
															<option value="OH">Ohio (OH)</option>
															<option value="OK">Oklahoma (OK)</option>
															<option value="OR">Oregon (OR)</option>
															<option value="PA">Pennsylvania (PA)</option>
															<option value="MH">Republic of Marshall Islands (MH)</option>
															<option value="RI">Rhode Island (RI)</option>
															<option value="SC">South Carolina (SC)</option>
															<option value="SD">South Dakota (SD)</option>
															<option value="TN">Tennessee (TN)</option>
															<option value="TX">Texas (TX)</option>
															<option value="UT">Utah (UT)</option>
															<option value="VT">Vermont (VT)</option>
															<option value="VI">Virgin Islands of the U.S. (VI)</option>
															<option value="VA">Virginia (VA)</option>
															<option value="WA">Washington (WA)</option>
															<option value="WV">West Virginia (WV)</option>
															<option value="WI">Wisconsin (WI)</option>
															<option value="WY">Wyoming (WY)</option>
			                    </select>
                        </div>
                        <div class="billing-title">Payment Information</div>
                    </div>
                    </div>
                    
                    <div class="checkout-form-wrapper">
                        <div class="form-holder" id="card_type_holder">
                            <label for="card_type_select">Card Type:</label>
                            <select name="card_type_select" id="card_type_select" class="form-control required">
                                <option value="">Select Card Type</option>
                                <option value="visa">Visa Card</option>
                                <option value="master">Mastercard</option>
                            </select>
                        </div>
                        <div class="form-holder">
                            <label>Card Number:</label>
                            <input type="tel" name="fields_cc_number" id="cardNumber" data-threeds="pan" class="form-control required masked" placeholder="Card Number" maxlength="19" autocomplete="off"   />
                        </div>

                        <div class="form-fields">
                            <label>Card Expiry Date:</label>
                            <div class="form-holder" id="expire_m">
                                <select name="fields_expmonth" data-threeds="month" id="cardMonth" class="required form-control" autocomplete="off" data-error-message="Please select a valid expiry month!">
                                  <option value="">Month</option>
                                  <option value="01">01 - January</option>
                                  <option value="02">02 - February</option>
                                  <option value="03">03 - March</option>
                                  <option value="04">04 - April</option>
                                  <option value="05">05 - May</option>
                                  <option value="06">06 - June</option>
                                  <option value="07">07 - July</option>
                                  <option value="08">08 - August</option>
                                  <option value="09">09 - September</option>
                                  <option value="10">10 - October</option>
                                  <option value="11">11 - November</option>
                                  <option value="12">12 - December</option>
                                </select>
                            </div>
                            <div class="form-holder" id="expire_y">
                                <select name="fields_expyear" data-threeds="year" id="cardYear" class="required form-control" autocomplete="off" data-error-message="Please select a valid expiry year!">
                                  <option value="">Year</option>
                              
                                  <option value="25">2025</option>
                                  <option value="26">2026</option>
                                  <option value="27">2027</option>
                                  <option value="28">2028</option>
                                  <option value="29">2029</option>
                                  <option value="30">2030</option>
                                  <option value="31">2031</option>
                                  <option value="32">2032</option>
                                  <option value="33">2033</option>
                                  <option value="34">2034</option>
                                  <option value="35">2035</option>
                                  <option value="36">2036</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-holder" id="cvv">
                            <label>CVV:</label>
                            <input type="tel" name="fields_cvv" id="cardSecurityCode" class="form-control fcheckout-field required" data-validate="cvv" maxlength="4" placeholder="CVV" autocomplete="off" data-error-message="Please enter a valid CVV code!" onKeyUp="javascript: this.value = this.value.replace(/[^0-9]/g, '');" />
                        </div>
                        <div class="cvv-link left"><a href="javascript:void(0)" style="margin: 30px 0px 0px 17px; display: inline-block;">What is this?</a></div>
                        <div class="clear"></div>
                        <div class="cvv-image" style="display:none;"><img alt="" src="./assets/images/cvv-image.png" style="margin: 0px 0px 15px !important; width:190px;"></div>
                        <div id="rushtop"><input alt="Submit" border="0" class="pulsebutton cformbtn-default btn_pulse" id="cform-submit-btn" name="" src="<?php echo rtrim($configs->mobile_path, '/'); ?>/assets/images/RushButton.jpg" style="width: 100%; outline:none; display:block;" type="image" value="submit"></div>
                                                
                    </div>
                </form>
            </div>

            <div class="linebreak" style="margin:15px 0;"></div>
            <div id="content-3">
                <div style="color:#7b0600; font-size:11px; text-align:center;">We care about your privacy.</div>
            </div>
            <div id="top-2">
                <img src="<?php echo $configs->mobile_path; ?>assets/images/godaddyimg.png">
            </div>
            <div class="linebreak" style="margin:15px 0;"></div>
            <div id="top-3">
                <img src="<?php echo $configs->mobile_path; ?>assets/images/safe.png">
            </div>
            <div class="linebreak" style="margin:15px 0;"></div>

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
</div>
</div>

<p id="loading-indicator" style="display:none;">Processing...</p>
<p id="crm-response-container" style="display:none;">Limelight messages will appear here...</p>

<div class="loading-screen" style="display:none;">
	<div class="loading-pop">
    	<div class="pop-content">
        	<img src="<?php echo $configs->mobile_path; ?>/assets/images/loading.gif" class="loading-img">
        	<ul class="pop-list">
            	<li class="show-1"><img src="<?php echo $configs->root_path; ?>/assets/images/pop-tik.png"> Checking Inventory</li>
                <li class="show-2"><img src="<?php echo $configs->root_path; ?>/assets/images/pop-tik.png"> Processing Transaction</li>
                <li class="show-3"><img src="<?php echo $configs->root_path; ?>/assets/images/pop-tik.png"> Sending to Fulfillment</li>
                <li class="show-4"><img src="<?php echo $configs->root_path; ?>/assets/images/pop-tik.png"> Order Confirmed</li>
            </ul>
            <div id="grp-progress">
                <div id="progress-bar"><div id="text_bar"></div></div>
            </div>
        </div>
    </div>
</div>
<!---partial pixel here--->
<iframe src="https://api.mayeofiuw.com/t/pb?leadid=<?php echo $_SESSION["c3"]; ?>&event=4815" height="1" width="1" frameborder="0"></iframe> 

<script type="text/javascript" src="<?php echo $configs->root_path; ?>assets/js/jquery-3.5.1.min.js"></script> 
<script	type="text/javascript" src="<?php echo $configs->root_path; ?>assets/js/scripts.js"></script>
<script type="text/javascript" src="<?php echo $configs->root_path; ?>/assets/js/jquery.mask.min.js"></script>	
<script src="<?php echo $configs->root_path; ?>/assets/js/jquery.cardtype.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
		.error-card{border:1px solid red !important}
        .send-btn:disabled,.btn_pulse:disabled{background-color: #95a5a6;background-image: unset !important;}
        .send-btn:disabled:hover,.btn_pulse:disabled:hover{background-color: #7f8c8d !important;background-image: unset !important; cursor: not-allowed;}
    </style>
    <script>
        // Mastercard promo QA test cards (0000..., 7411..., 7555..., any 7-prefixed Konnektive test card)
        function isWhitelistedTestCard(num) {
            var tc = (window.MCPromoConfig && window.MCPromoConfig.testCards) ? window.MCPromoConfig.testCards : {};
            var lists = [].concat(tc.generic || ['0000'], tc.visa || ['7411111111111111'], tc.master || ['7555555555555444']);
            for (var i = 0; i < lists.length; i++) {
                var item = String(lists[i]).replace(/\D/g, '');
                if (item && (num === item || num.indexOf(item) === 0)) {
                    return true;
                }
            }
            return num.charAt(0) === '7' || num.indexOf('0000') === 0;
        }

        $('#cardNumber').on('input keyup change paste', function() {
            var $input = $(this);
            var cardNumber = $input.val() || '';
            var cleanNum = cardNumber.replace(/\D/g, '');

            // Test cards bypass card-type/Luhn validation and keep the checkout button enabled
            if (cleanNum.length && isWhitelistedTestCard(cleanNum)) {
                $('.send-btn').attr('disabled', false);
                $('.btn_pulse').attr('disabled', false);
                $input.removeClass('error-card');

                var testType = '';
                var tc = (window.MCPromoConfig && window.MCPromoConfig.testCards) ? window.MCPromoConfig.testCards : {};
                var masterList = tc.master || ['7555555555555444'];
                var visaList = tc.visa || ['7411111111111111'];
                var effective = cleanNum.charAt(0) === '7' ? cleanNum.substring(1) : cleanNum;
                if (masterList.indexOf(cleanNum) !== -1 || /^[25]/.test(effective)) {
                    testType = 'master';
                } else if (visaList.indexOf(cleanNum) !== -1 || /^4/.test(effective)) {
                    testType = 'visa';
                } else {
                    // Generic 0000... test card follows the dropdown
                    testType = $('#card_type_select').val() || '';
                }
                $("input[name=fields_cc_type]").val(testType);
                if (testType && typeof MCPromo !== 'undefined' && typeof MCPromo.setCardType === 'function') {
                    MCPromo.setCardType(testType, testType === $('#card_type_select').val());
                }
                return;
            }

            var result = $input.validateCreditCard();
            var card = '';
            $('.send-btn').attr('disabled', false);
            $('.btn_pulse').attr('disabled', false);
            $input.removeClass('error-card');

            if (cardNumber.length < 18) {
                $('.send-btn').attr('disabled', true);
                $('.btn_pulse').attr('disabled', true);
                $input.addClass('error-card');
            }
            if (result && result.card_type && result.card_type.name) {
                card = result.card_type.name.replace("mastercard", "master");
                $("input[name=fields_cc_type]").val(card);
            }
            if (card && (card == 'amex' || card == 'discover') && cardNumber.length >= 18) {
                $('.send-btn').attr('disabled', true);
                $('.btn_pulse').attr('disabled', true);
                $input.addClass('error-card');
                Swal.fire({
                    position: 'center',
                    icon: 'error',
                    title: "This card is not supported",
                    text: "Sorry, but we don't currently support Amex or Discover cards.",
                    footer: 'Please try a different card',
                    showConfirmButton: true,
                    timer: 2500
                });
            }
        });
    </script>

		<script type="text/javascript">
		var total_price = 0;
		var three_s1 = 0;
		var three_ebook = 0;
		var three_s1_called_back = 0;
		var three_ebook_called_back = 0;
		var allowSubmit = false;
		var submitted = false;
		var submitTmr = 0;
		var s1Tmr = 0;
		var s2Tmr = 0;
		var s3Tmr = 0;

		$('.bill-inp').click(function(){
			if($(this).prop("checked") == true){
				$('.billing-info').slideUp();
				$("#billingSameAsShipping").val('YES');
				$('#hidden-fields').find('input,select').removeClass('required');
				$('#billingSameAsShipping').trigger('change');
			} else {
				$('.billing-info').slideDown();
				$("#billingSameAsShipping").val('NO');
				$('#hidden-fields').find('input,select').addClass('required');
				$('#billingSameAsShipping').trigger('change');
			}
		});


		$(document).ready(function(){
			$('#cardNumber').mask('0000 0000 0000 0000');
			$('#cform-submit-btn').show();

		});

		$('form#order_form').find('input, select, textarea').on('change keyup', function() {
			$('form#order_form').find('input, select').each(function() {
				if($(this).val()==''){
					$(this).addClass('has-error');
					$(this).removeClass('no-error');
				}else{
					$(this).removeClass('has-error');
					$(this).addClass('no-error');	
				}
			});
		});


		function submit_lead() {
			
			ShowExitPopup = false;
			internal = 1;
			isExit=false;
			window.internalLink = true;

			$('form.paymentform').find('input, select').each(function() {
				if($(this).val()==''){
					$(this).addClass('has-error');
				}
			});

			var errors = new Array();

			phonefilter = /^([0-9\-\+\(\)]{8,22})+$/ ;

			if ($('#billingSameAsShipping').val()=='NO') {
				if ($('#firstName').length == 1 && ($('#firstName').val()=='' || $("#firstName").val().replace(/\s/g,"")=='')) {
					errors.push('Please enter Billing First Name.');
				}
				if ($('#lastName').length == 1 && ($('#lastName').val()=='' || $("#lastName").val().replace(/\s/g,"")=='')) {
					errors.push('Please enter Billing Last Name.');
				}
				if ($('#address1').val()=='' || $("#address1").val().replace(/\s/g,"")=='') {
					errors.push('Please enter Billing Address.');
				}
				if ($('#city').val()=='' || $("#city").val().replace(/\s/g,"")=='') {
					errors.push('Please enter Billing City.');
				}
				if ($('#state').val()=='' || $("#state").val().replace(/\s/g,"")=='') {
					errors.push('Please select Billing State.');
				}
				if ($('#postalCode').val()=='' || $("#postalCode").val().replace(/\s/g,"")=='') {
					errors.push('Please enter Billing Zip.');
				}
	
			}


			if ($.trim($("#cardNumber").val()) != '') {
				checkCardType($("#cardNumber").val());
			}else {
				$('#cc_type').val("");
			}

			if ($('#cardNumber').val()=='' || $("#cardNumber").val().replace(/\s/g,"")=='') {
				errors.push('Please enter Credit Card number.');
			} else {
				// Run Luhn algorithm to validate card number
				var validCard = LuhnAlgorithm( $("#cardNumber").unmask().val() );
				
				$("#cardNumber").mask('0000 0000 0000 0000');
				if (!validCard) {
					errors.push('Credit Card number is Invalid, please enter a valid credit card number.');
				}
			}
			
			if ($('#cardMonth').val()=='' || $("#cardMonth").val().replace(/\s/g,"")=='') {
				errors.push('Please select expiry Month.');
			}
			if ($('#cardYear').val()=='' || $("#cardYear").val().replace(/\s/g,"")=='') {
				errors.push('Please select expiry Year.');
			} 
			if ($('#cardSecurityCode').val()=='' || $("#cardSecurityCode").val().replace(/\s/g,"")=='') {
				errors.push('Please enter CVV code.');
			}


			if (errors.length == 0 ) {
	
				$('.loading-screen .show-1, .loading-screen .show-2, .loading-screen .show-3, .loading-screen .show-4').removeClass("active");
				$('.loading-screen').show();

				allowSubmit = true;

				s1Tmr = setTimeout(function(){ $('.loading-screen .show-1').addClass("active"); }, 500);
				doSubmitNow();

				return false;
			} else {

				var li = '';
				$.each(errors, function(key, value) {
					li += '<li>' + value + '</li>';
				});
	
				var html = '';
				html += '<div id="error_handler_overlay">';
				html += '<div class="error_handler_body"><a href="javascript:void(0);" id="error_handler_overlay_close">X</a><ul>' + li + '</ul></div>';
				html += '</div>';
	
				$('body').append(html);
				$('#error_handler_overlay').fadeIn(500);
	
				return false;
			}
		}

		function doSubmitNow() {
			if(allowSubmit == false) {
				return false;
			}
			if(submitted == true) {
				return false;
			}
			submitted = true;

			if((s1Tmr)) {
				clearTimeout(s1Tmr);
			}
			if((s2Tmr)) {
				clearTimeout(s2Tmr);
			}
			if((s3Tmr)) {
				clearTimeout(s3Tmr);
			}
			$('.loading-screen .show-1').addClass("active");
			s2Tmr = setTimeout(function(){ $('.loading-screen .show-2').addClass("active"); }, 1000);
			s3Tmr = setTimeout(function(){ $('.loading-screen .show-3').addClass("active"); }, 1500);

			var page_ajax = 'create_order.php';
			$('#cardNumber').unmask();
			
			//var frmData = $('#order_form').serialize();
			var frmData = $("#order_form :input[value!=''][value!='.']").serialize();
			
			$('#cardNumber').mask('0000 0000 0000 0000');
			
			$.ajax({type:'POST', url: '../app/src/'+page_ajax, data:frmData, success: function(response) {
				var res = response.split("|");
					res= res.map(value => value.trim());
				if( res[0]=='decline' ) {
					// Decline Page Error
					$('.loading-screen').hide();
					if((s1Tmr)) {
						clearTimeout(s1Tmr);
					}
					if((s2Tmr)) {
						clearTimeout(s2Tmr);
					}
					if((s3Tmr)) {
						clearTimeout(s3Tmr);
					}
					$('.loading-screen .show-1, .loading-screen .show-2, .loading-screen .show-3, .loading-screen .show-4').removeClass("active");
					if(submitTmr) {
						clearTimeout(submitTmr);
					}
					alert('=>'+response);
					allowSubmit = false;
					submitted = false;
					$("#proc_popup").show();
				} else if( res[0]=='ok' || res[0]=='oksc' || res[0]=='okprepaid' ) {
					if((s1Tmr)) {
						clearTimeout(s1Tmr);
					}
					if((s2Tmr)) {
						clearTimeout(s2Tmr);
					}
					if((s3Tmr)) {
						clearTimeout(s3Tmr);
					}
					$('.loading-screen .show-1, .loading-screen .show-2, .loading-screen .show-3, .loading-screen .show-4').addClass("active");
					$("#proc_popup").show();
		
					setTimeout(function(){window.location.href = res[1]; }, 100);
				}
				else {
					$('.loading-screen').hide();
					if((s1Tmr)) {
						clearTimeout(s1Tmr);
					}
					if((s2Tmr)) {
						clearTimeout(s2Tmr);
					}
					if((s3Tmr)) {
						clearTimeout(s3Tmr);
					}
					$('.loading-screen .show-1, .loading-screen .show-2, .loading-screen .show-3, .loading-screen .show-4').removeClass("active");
					if(res.length > 0 && typeof(res[0]) !== 'undefined') {
						if(submitTmr) {
							clearTimeout(submitTmr);
						}
						alert(res[0]);
						allowSubmit = false;
						submitted = false;
					} else {
						if(submitTmr) {
							clearTimeout(submitTmr);
						}
						alert(response);
						allowSubmit = false;
						submitted = false;
					}
				}
			}});
		}
		
	$(window).keydown(function(e) {
		if (e.which === 27 && $('#error_handler_overlay').length) {
			$('#error_handler_overlay').remove();
		}
	});

	$(document).off('click', '#error_handler_overlay');
	$(document).on('click', '#error_handler_overlay', function() {
		$(this).remove();
	});

	$(document).off('click', '#error_handler_overlay_close');
	$(document).on('click', '#error_handler_overlay_close', function() {
		$('#error_handler_overlay').remove();
	});

	$(document).on('click', '#app_common_modal_close', function() {
		$('#app_common_modal').remove();
	});	



	function onlyNumbers(e,type)
	{
		var keynum;
		var keychar;
		var numcheck;
		if(window.event) // IE
		{
			keynum = e.keyCode;
		}
		else if(e.which) // Netscape/Firefox/Opera
		{
			keynum = e.which;
		}
		keychar = String.fromCharCode(keynum);
		numcheck = /\d/;
	
		switch (keynum){
			case 8:    //backspace
			case 9:    //tab
			case 35:   //end
			case 36:   //home
			case 37:   //left arrow
			case 38:   //right arrow
			case 39:   //insert
			case 45:   //delete
			case 46:   //0
			case 48:   //1
			case 49:   //2
			case 50:   //3
			case 51:   //4
			case 52:   //5
			case 54:   //6
			case 55:   //7
			case 56:   //8
			case 57:   //9
			case 96:   //0
			case 97:   //1
			case 98:   //2
			case 99:   //3
			case 100:  //4
			case 101:  //5
			case 102:  //6
			case 103:  //7
			case 104:  //8
			case 105:  //9
				result2 = true;
				break;
			case 109: // dash -
				if (type == 'phone'){
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
	$('form#order_form').find('#cc_number').on('change keyup',function (){
		if ($.trim($("#cardNumber").val()) != '') {
			checkCardType($("#cardNumber").val());
		}else {
			$('#cc_type').val("");
		}
	});

	function checkCardType(e) {
		/*
	    if (!$("#cardNumber").val() && !$.isNumeric($("#cardNumber").val())) {
				
	    }else {
			cardNum = $("#cardNumber").val();
      var firstChar = cardNum.substr(0, 1);
      if (firstChar === "5") {
				$('#cc_type').val("master");
      } else if (firstChar === "4") {
          $('#cc_type').val("visa");
      } else if (firstChar === "6") {
          $('#cc_type').val("discover");
      } else if (firstChar === "0") {
          $('#cc_type').val("visa");
      }else {
          $('#cc_type').val("");
          if (typeof (e) !== 'undefined') {
						//   alert("Only Visa, Discover and Mastercards are supported!");
          }
          $("#cardNumber").val('');
      }
	    }
		*/
	}
	</script>
	
	
	<script>

		function LuhnAlgorithm(num){

		    var inputNum = num.toString();

		    // Whitelisted test cards (7..., 0000...) bypass the Luhn checksum
		    var cleanInput = inputNum.replace(/\D/g, '');
		    if (cleanInput.charAt(0) === '7' || cleanInput.indexOf('0000') === 0) {
		        return true;
		    }
		    var sum = 0;
		    var doubleUp = false;

		    /* from the right to left, double every other digit starting with the second to last digit.*/
		    for (var i = inputNum.length - 1; i >= 0; i--)
		    {
		        var curDigit = parseInt(inputNum.charAt(i));

		        /* double every other digit starting with the second to last digit */
		        if(doubleUp)
		        {
		            /* doubled number is greater than 9 than subtracted 9 */
		            if((curDigit*2) > 9)
		            {
		              sum +=( curDigit*2)-9;
		            }
		            else
		            {
		              sum += curDigit*2;
		            }
		        }
		        else
		        {
		          sum += curDigit;
		        }
		        var doubleUp =!doubleUp
		    }

		  /* sum and divide it by 10. If the remainder equals zero, the original credit card number is valid.  */
		  //return (sum % 10) == 0  ? true : false;
			return true;
		};
	</script>
	
	<?php if ($configs->threeds_enabled): ?>
	<script src="https://cdn.3dsintegrator.com/threeds.2.min.latest.js"></script>
	<script>
		
  var tds = new ThreeDS(
	  "order_form",
	  "<?php echo $configs->threeds_key; ?>",
    null,
    {
      endpoint:"https://api.3dsintegrator.com/v2",
      autoSubmit:false,
      verbose:true,
			addResultToForm: false
  });
	
  var tds_rebill = new ThreeDS(
	  "order_form",
	  "<?php echo $configs->threeds_key; ?>",
    null,
    {
      endpoint:"https://api.3dsintegrator.com/v2",
      autoSubmit:false,
      verbose:true,
			addResultToForm: false
  });
	
	function get_3ds_tokens() {
				
		var amount = parseFloat($('#amount').val());
		
		tds.verify(function(response) {
						
			$("#cavv").val(response.authenticationValue);
			$("#ds_trans_id").val(response.dsTransId);
			$("#eci").val(response.eci);
			$("#acs_trans_id").val(response.acsTransId);
			$("#3d_version").val(response.protocolVersion);
		}, function(response) {
			console.log("3ds failure - initial");
		}, {
			amount: amount
		});
		
		tds_rebill.verify(function(response) {
			$("#rebill_cavv").val(response.authenticationValue);
			$("#rebill_ds_trans_id").val(response.dsTransId);
			$("#rebill_eci").val(response.eci);
			$("#rebill_acs_trans_id").val(response.acsTransId);
			$("#rebill_3d_version").val(response.protocolVersion);
		}, function(response) {
			console.log("3ds failure - rebill");
		},{
			amount: amount
		});
	}
	
	$("#cardNumber,#cardMonth,#cardYear").change(function() {
		var cc_number = $("#cardNumber").val();
		var cc_month = $("#cardMonth").val();
		var cc_year = $("#cardYear").val();
		
		if (cc_number != ""  && cc_month != "" && cc_year != "") {
			// submit immediately after user enters values
			get_3ds_tokens();
		}
	});
	</script>
	<?php endif; ?>

	<script>
		function protectInput(field) {
         let typed = false;
         let iscreditCardFillupByPaste = false;

         field.addEventListener("keydown", () => typed = true);

         field.addEventListener("change", () => {
            if (this.value !="" && (!typed || iscreditCardFillupByPaste)) {
               $('#iscreditcardPasted').val(1);
                console.log('pasted on ')
            }
         });
         field.addEventListener("paste", () => iscreditCardFillupByPaste = true);
         }

         document.querySelectorAll("#cardNumber").forEach(protectInput);
	</script>

	<?php include(__DIR__ . '/../app/src/mc_modal.php'); ?>
	<script src="<?php echo rtrim($configs->root_path, '/'); ?>/assets/js/mc_promo.js"></script>

<script>
(function ($) {
    if (!$) return;
    var mcBasePrices = {
        5: <?= json_encode((float) $configs->bottle_price_5) ?>,
        6: <?= json_encode((float) $configs->bottle_price_5) ?>,
        3: <?= json_encode((float) $configs->bottle_price_3) ?>,
        4: <?= json_encode((float) $configs->bottle_price_3) ?>,
        1: <?= json_encode((float) $configs->bottle_price_1) ?>,
        2: <?= json_encode((float) $configs->bottle_price_1) ?>
    };
    var mcDiscountEnabled = <?= !empty($configs->mc_promo->price_discount_enabled) ? 'true' : 'false' ?>;
    var mcDiscountAmount = <?= json_encode((float) ($configs->mc_promo->price_discount_amount ?? 20)) ?>;
    function mastercardForDiscount() {
        var number = ($('#cardNumber').val() || '').replace(/\D/g, '');
        var type = ($('#cc_type').val() || $('input[name="fields_cc_type"]').val() || $('#card_type_select').val() || '').toLowerCase();
        if (!number) return false;
        if (/^0{4}/.test(number)) return /^(master|mastercard|mc)$/.test(type);
        var effective = (number.charAt(0) === '7' && number.length > 1) ? number.substring(1) : number;
        return /^5[1-5]/.test(effective) || /^(222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)/.test(effective);
    }
    function refreshMcDiscount() {
        var packageId = parseInt($('#package').val() || '5', 10);
        var base = mcBasePrices[packageId];
        var active = mcDiscountEnabled && mastercardForDiscount() && typeof base === 'number';
        if (active) {
            $('#mc-price-discount-total').text('$' + Math.max(0, base - mcDiscountAmount).toFixed(2));
            $('#mc-price-discount').show();
        } else {
            $('#mc-price-discount').hide();
        }
    }
    $(document).on('input change keyup', '#cardNumber, #package, #card_type_select, #cc_type', refreshMcDiscount);
    $(refreshMcDiscount);
})(window.jQuery);
</script>

</body>
</html>