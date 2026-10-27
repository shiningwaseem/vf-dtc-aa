<?php
	
	include('../_global.php');

	if( !empty($_GET['shipFirstName']) ) {
		$shipFirstName = $_GET['shipFirstName'];
	} else {
		$shipFirstName = "";
	}
	if( !empty($_GET['shipLastName']) ) {
		$shipLastName = $_GET['shipLastName'];
	} else {
		$shipLastName = "";
	}
	if( !empty($_GET['emailAddress']) ) {
		$emailAddress = $_GET['emailAddress'];
	} else {
		$emailAddress = "";
	}
	if( !empty($_GET['phoneNumber']) ) {
		$phoneNumber = $_GET['phoneNumber'];
	} else {
		$phoneNumber = "";
	}


	$product_price = 0.0;
	$package = 0;
	if(!empty($_REQUEST['pk'])) {
		if($_REQUEST['pk']==1){
			$package = 1;
		} else if($_REQUEST['pk']==3){
			$package = 3;
		} else if($_REQUEST['pk']==5){
			$package = 5;
		}
	}
	// if(empty($package)) {
	// 	$homeUrl = '/ed/mobile/choose.php?' . (!empty($_SERVER['QUERY_STRING']) ? ('?' . $_SERVER['QUERY_STRING']) : '');
	// 	header('Location:' . $homeUrl);
	// 	exit(0);
	// }

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?php echo $configs->step1_name; ?></title>
<meta content="width=device-width, initial-scale=1" name="viewport">
<link href="<?php echo $configs->mobile_path .'assets/brand/favicon.png';?>" rel="icon" type="image/png">

<link href="<?php echo $configs->mobile_path . "/assets/css/app.css"; ?>" rel="stylesheet">
<link href="<?php echo $configs->mobile_path ."assets/css/inner.css"; ?>" type="text/css" rel="stylesheet">
<!-- <link href="<?php echo $configs->root_url . "/assets2/css/jquery.autocomplete.css"; ?>" rel="stylesheet"> -->
<style>
.autocomplete-suggestions { overflow: auto; width:auto !important;}
.autocomplete-suggestions { background-color: white !important; padding:20 0px;}
.autocomplete-suggestion { overflow: visible; }
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
   <div id="pagecontainer">

        <div id="top-1">
            <center><img src="<?php echo $configs->mobile_path; ?>assets/brand/logo.png" style="width:158px; margin: 6px 0 10px 0;"></center>
            <div class="shipping-container">
                <span><strong>Fast, Free Shipping</strong> For A Limited Time</span>
            </div>
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
                <b style="color:#f18704; font-size:18px;">Your package is reserved for the next: <span id="clockdiv"> 9:56</span> Minutes <br><br><span style="color:#000;"> Where do we send it?</span></b><br></p>

            <div class="form-partial" id="partialcontainer" style="max-width:95%; margin:0 auto;">
							
                <form  name="prospect_form1" class="partialform form form123" id="theForm" action="../app/src/create_prospect.php" method="post" onSubmit="return validate_optin_form()" accept-charset="utf-8" enctype="application/x-www-form-urlencoded;charset=utf-8">
									
										<input type='hidden' name='token' value='<?php echo $_SESSION["token"]; ?>'>
										<input type='hidden' name='AFFID' value='<?php echo isset($_GET['AFFID'])?$_GET['AFFID']:$_SESSION["affid"]; ?>'>
										<input type="hidden" name="device" value="mobile">
	                  <input type="hidden" name="notes" value="<?php echo $_GET["notes"];?>" />
        
	                  <?php
		                  foreach($_GET as $key => $value) {
		                  	echo "<input type='hidden' name='".safeRequest($key)."' value='".safeRequest($_GET[$key])."'>";
		                  }
	                  ?>    
                    <div class="shipping-form-wrapper">
                        <div class=" columns form-holder">
                            <label>First Name:</label>
                            <input class="form-control required" data-placement="auto left" name="fields_fname" id='fields_fname' placeholder="First Name*" title="First Name" type="text" value="<?php echo $shipFirstName; ?>">
                        </div>

                        <div class=" columns form-holder">
                            <label>Last Name:</label>
                            <input class="form-control required" data-placement="auto left" name="fields_lname" id='fields_lname' placeholder="Last Name*" title="Last Name" type="text" value="<?php echo $shipLastName; ?>">
                        </div>

                        <div class=" form-holder">
                            <label>Zip Code:</label>
                            <input class="form-control required" data-placement="auto left" name="fields_zip" id="fields_zip" placeholder="Zip Code*" minlength="5" maxlength="5" title="Zip Code" type="tel" value="" onKeyUp="javascript: this.value = this.value.replace(/[^0-9]/g, '');">
                        </div>

                        <div class=" form-holder">
                            <label>Address:</label>
                            <input class="form-control required" data-placement="auto left" name="fields_address1" id="fields_address1" placeholder="Address*" title="Address" type="text" value="">
                        </div>

                        <div class="form-holder">
                            <label>City:</label>
                            <input class="form-control required" data-placement="auto left" name="fields_city" id="fields_city" placeholder="City*" title="City" type="text" value="">
                        </div>

                        <div class="form-holder" style="display: none">
                            <label>Country</label>
                            <select class="form-control" id="id_country" >
                            </select>
                        </div>

                        <div class=" form-holder">
                            <label>State:</label>
                            <select name="fields_state" id="fields_state" class="form-control required" data-selected="" data-error-message="State is required">
                        			<option value="" onClick="" >Select State</option>
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
                        <div class=" columns">
                            <label>Email:</label>
                            <input class="form-control required" data-placement="auto left" name="fields_email" id="fields_email" placeholder="Email*" title="Email" type="email" value="<?php echo $emailAddress; ?>">
                        </div>

                        <div class=" columns">
                            <label>Phone Number</label>
                            <input class="form-control required" data-placement="auto left" name="fields_phone" id="fields_phone" placeholder="Phone Number*" title="Phone Number" type="tel" value="<?php echo $phoneNumber; ?>" maxlength="10">
                        </div>

                        <button id="partialsubmitbutton" name="partialsubmitbutton" style="font-size: 21px;" type="submit"> Proceed to Checkout »
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="linebreak" style="margin:15px 0;"></div>
        <div id="content-3">
            <div style="color:#7b0600; font-size:11px; text-align:center;">
                We care about your privacy.
            </div>
        </div>
        <div id="top-2">
            <img src="<?php echo $configs->mobile_path; ?>/assets/images/godaddyimg.png">
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
                    <p><?php echo $configs->step1_name; ?> is committed to maintaining the highest quality products and the utmost integrity in business practices. All products sold on this website are certified by Good Manufacturing Practices (GMP), which is the highest standard of testing in the supplement industry.</p><br><p>Notice: The products and information found on this site are not intended to replace professional medical advice or treatment. These statements have not been evaluated by the Food and Drug Administration. These products are not intended to diagnose, treat, cure or prevent any disease. Individual results may vary.</p>
                </div>
                <br>
            </center>
    
            <center>
                <div class="text-center">
                    <p class="cop-text">&copy; <script> var date = new Date(); document.write(date.getFullYear()) </script> <span class="product-name"><?php echo $configs->corporate_name; ?></span>. All Rights Reserved.</p>
                    <br>
                </div>
            </center>
            <p></p>
    	</div>
	</div>
</div>

<p id="loading-indicator" style="display:none;">Processing...</p>
<p id="crm-response-container" style="display:none;">Limelight messages will appear here...</p>

<script type="text/javascript" src="<?php echo $configs->root_path; ?>assets/js/jquery-3.5.1.min.js"></script> 
<script type="text/javascript" src="<?php echo $configs->root_path; ?>assets/js/jquery.mask.min.js"></script>	
<script type="text/javascript">
$(document).ready(function () {
  $('#fields_phone').mask('000-000-0000');

  var time_in_minutes = 10;
  var current_time = Date.parse(new Date());
  var deadline = new Date(current_time + time_in_minutes * 60 * 1000);

  function time_remaining(endtime) {
    var t = Date.parse(endtime) - Date.parse(new Date());
    var seconds = Math.floor((t / 1000) % 60);
    var minutes = Math.floor((t / 1000 / 60) % 60);
    var hours = Math.floor((t / (1000 * 60 * 60)) % 24);
    var days = Math.floor(t / (1000 * 60 * 60 * 24));
    return {
      total: t,
      days: days,
      hours: hours,
      minutes: minutes,
      seconds: seconds,
    };
  }

  function run_clock(id, endtime) {
    var clock = document.getElementById(id);

    function update_clock() {
      var t = time_remaining(endtime);
      clock.innerHTML = " " + t.minutes + ":" + t.seconds;
      if (t.total <= 0) {
        clearInterval(timeinterval);
      }
    }

    update_clock();
    var timeinterval = setInterval(update_clock, 1000);
  }

  run_clock("clockdiv", deadline);
});

</script>

<script type="text/javascript" src="<?php echo $configs->root_path . "/assets/js/jquery.autocomplete.min.js"; ?>"></script>
<script>
// <?php if ($configs->smartystreets_autocomplete_enabled): ?>
// var smtKey = '<?php echo $configs->smartystreets_key; ?>';
// var smtAuto = <?php echo $configs->smartystreets_autocomplete_enabled; ?>;
// var smtVerify = <?php echo $configs->smartystreets_verification_enabled; ?>;
// var smartUrl = "https://us-autocomplete-pro.api.smartystreets.com/lookup?key=" + smtKey + "&prefer_geolocation=city";
// var lastAddress1 = '';
// var lastAddress2 = '';

if(typeof smtKey !== "undefined" && smtKey != '') {
	function submitAddress2() {
		if(lastAddress1 == '') {
			lastAddress1 = $('input[name="fields_address1"]').val();
			lastAddress1.replace('"', '');
		}
		lastAddress2 = $('input[name="fields_address2"]').val();
		lastAddress2.replace('"', '');
		var combAddr = lastAddress1 + ', ' + lastAddress2;
		$('input[name="fields_address1"]').val(combAddr);
		$('#app_common_modal').remove();
		$('#theForm').trigger('submit');
		return false;
	}

	var utils2 = (function () {
		return {
			escapeRegExChars: function (value) {
				return value.replace(/[|\\{}()[\]^$+*?.]/g, "\\$&");
			},
			createNode: function (containerClass) {
				var div = document.createElement('div');
				div.className = containerClass;
				div.style.position = 'absolute';
				div.style.display = 'none';
				return div;
			}
		};
	}());

	$(document).ready(function(ee) {
		if(typeof smtAuto !== "undefined" && smtAuto == 1) {
			$('#fields_address1').autocomplete({
				serviceUrl: smartUrl,
				paramName: "search",
				ajaxSettings: {},
				minChars: 2,
				deferRequestBy: 400,
				transformResult: function(response) {
					if (typeof response === "undefined") { 
						return {
							"suggestions": []
						};
					} else if (typeof response == "string") { 
						respJson = JSON.parse(response); 
					} else {
						respJson = response;
					}
					if(!(respJson) || typeof respJson.suggestions === "undefined" || respJson.suggestions == null) {
						return {
							"suggestions": []
						};
					}
					
					return {
						suggestions: $.map(respJson.suggestions, function(dataItem) {
							return { value: dataItem.street_line + (dataItem.secondary != '' && dataItem.entries == 1 ? (', ' + dataItem.secondary) : '') , data: dataItem};
						})
					};
				},
				formatResult: function (suggestion, currentValue) {
					if(typeof suggestion.data === "undefined") {
						return "";
					} else {
						if (!currentValue) {
							return suggestion.data.text;
						}
				
						var pattern = '(' + utils2.escapeRegExChars(currentValue) + ')';
						
						var addrfull = suggestion.data.street_line + (
							suggestion.data.secondary != '' ? ( 
								suggestion.data.entries > 1 ? (', ' + suggestion.data.secondary + ' (' + suggestion.data.entries + ' entries)') : (', ' + suggestion.data.secondary) 
							) : '') + ', ' + suggestion.data.city + ' ' + suggestion.data.state + ' ' + suggestion.data.zipcode;  
				
						return addrfull
							.replace(new RegExp(pattern, 'gi'), '<strong>$1<\/strong>')
							.replace(/&/g, '&amp;')
							.replace(/</g, '&lt;')
							.replace(/>/g, '&gt;')
							.replace(/"/g, '&quot;')
							.replace(/&lt;(\/?strong)&gt;/g, '<$1>');
					}
				},
				onSelect: function (suggestion) {
					if($('#fields_city').length == 1 && (suggestion.data.city)) {
						$('#fields_city').val(suggestion.data.city);
					}
					if($('#fields_state').length == 1 && (suggestion.data.state)) {
						$('#fields_state').val(suggestion.data.state);
					}
					if($('#fields_zip').length == 1 && (suggestion.data.zipcode)) {
						$('#fields_zip').val(suggestion.data.zipcode);
					}
								
				}
			});
		}
	});

}
<?php endif; ?>

$('form.form123').find('#fields_email').on('change keyup', function() {
	var emailReg2 = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
	if ( !emailReg2.test($('#fields_email').val()) ) {
		$(this).addClass('has-error');
		$(this).removeClass('no-error');
	}
});

$('form.form123').find('#fields_zip').on('change keyup', function() {
	var zip = $('#fields_zip').val();
	if ((zip.length)< 5 ){
		$(this).addClass('has-error');
		$(this).removeClass('no-error');
	}
});

$('form#theForm').find('input, select, textarea').on('change keyup', function() {
	$('form#theForm').find('input, select').each(function() {
		if($(this).val()==''){
			$(this).addClass('has-error');
			$(this).removeClass('no-error');
		}else{
			$(this).removeClass('has-error');
			$(this).addClass('no-error');	
		}
	});
});


function validate_optin_form(){
	ShowExitPopup = false;
	internal = 1;
	isExit=false;

	var errors = new Array();
	var phonefilter = /^([0-9\-\+\(\)]{8,22})+$/ ;
	var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
	//var is_terms_condtion_cheked = $('#agree_terms').prop('checked'); 
	//console.log($('#fields_fname').val().replace(/^\s+|\s+$/g,"")==''));

	$("input").removeClass("has-error");
	
	if ($('#fields_fname').val().replace(/^\s+|\s+$/g,"")=='') {
		errors.push('Please enter your first name');
		$('#fields_fname').addClass("has-error");
	}
	if ($('#fields_lname').val().replace(/^\s+|\s+$/g,"")=='') {
		errors.push('Please enter your last name');
		$('#fields_lname').addClass("has-error");
	}
	if ($('#fields_address1').val().replace(/^\s+|\s+$/g,"")=='') {
		errors.push('Please select your address');
		$('#fields_address1').addClass("has-error");
	}
	if ($('#fields_city').val().replace(/^\s+|\s+$/g,"")=='') {
		errors.push('Please enter your city');
		$('#fields_city').addClass("has-error");
	}
	if ($('#fields_state').val().replace(/^\s+|\s+$/g,"")=='') {
		errors.push('Please enter your State');
		$('#fields_state').addClass("has-error");
	}
	if ($('#fields_zip').val().replace(/^\s+|\s+$/g,"")=='') {
		errors.push('Please enter your Zip Code');
		$('#fields_zip').addClass("has-error");
	}
	if ($('#fields_phone').val().replace(/^\s+|\s+$/g,"")=='') {
		errors.push('Please enter your phone number');
		$('#fields_phone').addClass("has-error");
	}
	if ($('#fields_email').val().replace(/^\s+|\s+$/g,"")=='') {
		errors.push('Please enter your email address');
		$('#fields_email').addClass("has-error");
	} else if ( !emailReg.test($('#fields_email').val()) ) {
		errors.push('Please enter valid email address');
		$('#fields_email').addClass("has-error");
	}
	
	/*if($('#agree_terms:checked').length == 0) {
	errors.push('Please accept our terms and condition');
	}*/
	
	
	if(typeof smtKey !== "undefined" && smtKey != '' && typeof smtVerify !== "undefined" && smtVerify == 1) {
		if (errors.length == 0 ) {
			var addr = $('input[name="fields_address1"]').val() + ', ' + $('input[name="fields_city"]').val() + ' ' + $('select[name="fields_state"]').val() + ' ' + $('input[name="fields_zip"]').val() ;
			var errmsg = "";
			var vcode = "";
			var apiUrl = "https://us-street.api.smartystreets.com/street-address"
				+ "?street=" + encodeURI(addr)
				+ "&key=" + smtKey;
			$.ajax({
			  url: apiUrl,
			  method: "get",
			  async: false,
			  beforeSend: function( xhr ) {
			  }
			})
			.done(function(resData) {
				var errmsg = "";
				if(!(resData) || !(resData[0]) || !(resData[0].analysis) || !(resData[0].analysis.dpv_match_code)) {
					vcode = "";
				} else {
					vcode = resData[0].analysis.dpv_match_code;
				}
				
				if(vcode != '' && vcode != 'N' && vcode != 'D') {
					if(typeof resData[0].delivery_line_1 !== "undefined" && resData[0].delivery_line_1 != "") {
						$('input[name="fields_address1"]').val(resData[0].delivery_line_1);
						lastAddress1 = $('input[name="fields_address1"]').val();
					}
					
					if(typeof resData[0].components !== "undefined") {
						if(typeof resData[0].components.city_name !== "undefined" && resData[0].components.city_name != "") {
							$('input[name="fields_city"]').val(resData[0].components.city_name);
						}
						if(typeof resData[0].components.zipcode !== "undefined" && resData[0].components.zipcode != "") {
							$('input[name="fields_zip"]').val(resData[0].components.zipcode);
						}
						if(typeof resData[0].components.state_abbreviation !== "undefined" && resData[0].components.state_abbreviation != "") {
							$('select[name="fields_state"]').val(resData[0].components.state_abbreviation);
						}
					}
					
				}
			})
			.fail(function() {
			})
			.always(function() {
			});
			
			
			if(vcode == '') {
				errors.push('Address could not be found');
			} else if(vcode == 'N') {
				errors.push('Address does not seem to be deliverable');
			} else if(vcode == 'D') {
				
				var html = '';
				html += '<div id="app_common_modal">';
				html += '<div class="app_modal_body" style="text-align:center;min-height:150px;"><a href="javascript:void(0);" id="app_common_modal_close">X</a>';
	
				html += '<div>'
					+ '<form method="get" action="" onSubmit="return submitAddress2();">'
					+ '<h4>' + "Please enter your Apartment/Suite/Unit # to proceed" + '</h4>'
					+ '<input type="text" name="fields_address2" id="fields_address2" placeholder="" autocomplete="off" required style="width:80%;line-height:22px;margin:10px;padding:5px" maxlength="50" value="'+ (lastAddress2 != '' ? lastAddress2 : '') +'" data-error-message="Please enter your address!" />'
					+ '<button type="submit" style="width:40%;height:30px;line-height:22px;cursor:pointer;">OK</button>'
					+ '</form>';
					+ '</div>';
	
				html += '</div>';
				html += '</div>';
				
				if (!$('#app_common_modal').length) {
					$('body').append(html);
				}
				$('#app_common_modal').fadeIn(500);
				return false;
				
			}
			
		}
	}
	
	
	if (errors.length == 0 ) {
		$('#loading-indicator').show();
		$('#fields_phone').unmask();
		document.getElementById('theForm').submit();
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


$(function() {
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
		//     alert('close');
		$('#app_common_modal').remove();
	});	
});

</script> 
<script>
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
//End Added
        $('input[name*="fields_zip"]').blur(function(e) {
            let group = $(this).data('group');
            let country = $('[name*="country"]').val() ? $('[name*="country"]').val()  : 'US'; 
            
            $.ajax({
                url: "https://api.zippopotam.us/"+country+"/" + $(this).val(),
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    console.log(data);
                    try {
                        if (data.places[0]["state abbreviation"]) {
                            $('[name*="fields_state"]').val(data.places[0]["state abbreviation"]);    
                            
                            $('[name*="fields_state"]').trigger('blur');
                        }
                        if (data.places[0]["place name"]) {
                            $('[name*="fields_city"]').val(data.places[0]["place name"]);   
                            
                            $('[name*="fields_city"]').trigger('blur');
                        }
                    } catch(e) {}
                },
                error: function(xhr) { console.log(xhr.status); }
            });
        });
</script>
</body>
</html>