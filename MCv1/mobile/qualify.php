<?php

	include('../_global.php');
	$notes = "";

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?php echo $configs->step1_name;?></title>
<meta content="width=device-width, initial-scale=1" name="viewport">
<link href="<?php echo $configs->root_path . "/assets/brand/favicon.png"; ?>" rel="icon" type="image/png">

<link href="<?php echo $configs->root_path . "/assets/css/app.css"; ?>" rel="stylesheet">
<link href="./assets/css/inner.css" type="text/css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&family=Oswald:wght@400;500;600;700&family=Roboto+Condensed:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
<link href="<?php echo $configs->root_path . "/assets/css/jquery.autocomplete.css"; ?>" rel="stylesheet">
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
            <center>
                <img src="./assets/brand/logo.png" style="width:158px; margin: 6px 0 10px 0;">
            </center>

            <div class="breadcrumbs">
                <ul class="breadcrumbs__list">
                    <li class="breadcrumbs__item breadcrumbs__item_active">
                        <span>Qualify Now</span>
                    </li>
                    <li class="breadcrumbs__item breadcrumbs__item_2">
                        <span>Select Package</span>
                    </li>
                    <li class="breadcrumbs__item breadcrumbs__item_3">
                        <span>Confirm Order</span>
                    </li>
                </ul>
            </div>

            <p style="text-align:center;">
                <br>
                <b style="color:#f18704; font-size:19px; padding: 0 5px">Claim Your Male Enhancement Package Today!</b><br>
                <br>
                
                <br>
            </p>
            </div>

            <div class="form-partial" style="max-width:95%; margin:0 auto;">

               <form name="qualify_form1" class="kform form123" id="theForm" action="../app/src/mobile_qualify.php" method="post" onsubmit="return validate_optin_form()" accept-charset="utf-8" enctype="application/x-www-form-urlencoded;charset=utf-8" _lpchecked="1">
								 
						
				            <input type="hidden" name="notes" value="<?php echo $notes;?>" class="has-error">
				            <input type="hidden" name="country" id="country" value="US" class="no-error">    
						
				            <?php
					            foreach($_GET as $key => $value) {
					            	echo "<input type='hidden' name='".safeRequest($key)."' value='".safeRequest($_GET[$key])."'>";
					            }
				            ?>

                    <div class="lead-form-wrapper">
                        <div class=" columns form-holder">
                            <label>First Name:</label>
                            <input class="form-control required" data-placement="auto left" name="shipFirstName" id='fields_fname' placeholder="First Name*" title="First Name*" type="text" value="">
                        </div>
                        <div class=" columns form-holder">
                            <label>Last Name:</label>
                            <input class="form-control required" data-placement="auto left" name="shipLastName" id='fields_lname' placeholder="Last Name*" title="Last Name*" type="text" value="">
                        </div>
                        <div class=" columns">
                            <label>Email:</label>
                            <input class="form-control required" data-placement="auto left" name="emailAddress" id='fields_email' placeholder="Email*" title="Email" type="email" value="">
                        </div>
                        <div class=" columns">
                            <label>Phone:</label>
                            <input class="form-control required" data-placement="auto left" name="phoneNumber" id="fields_phone" placeholder="Phone Number*"  title="Phone Number" type="tel" value="" maxlength="10">
                        </div>
                       
                        <br>
                        <input alt="Submit" border="0" class="pulsebutton btn_pulse qualify-btn"
                               name="partialsubmitbutton" src="./assets/images/qualify-btn.jpg" style="width: 100%; outline:none;" type="image" value="submit">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="linebreak" style="margin:15px 0;">
    </div>

    <div>
        <img alt="" src="./assets/images/godaddyimg.png">
    </div>

    <div class="linebreak" style="margin:15px 0;">
    </div>

    <div>
        <img alt="" src="./assets/images/safe.png">
    </div>

    <div class="linebreak" style="margin:15px 0;">
    </div>

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

<p id="loading-indicator" style="display:none;">Processing...</p>
<p id="crm-response-container" style="display:none;"></p>

<script type="text/javascript" src="./assets/js/jquery-3.5.1.min.js"></script> 
<script type="text/javascript" src="<?php echo $configs->root_path . "/assets/js/jquery.autocomplete.min.js"; ?>"></script>
<script type="text/javascript" src="./assets/js/jquery.mask.min.js"></script>	
<script>
var lastAddress1 = '';
var lastAddress2 = '';

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

	$("input").removeClass("has-error");
	
	if ($('#fields_fname').val().replace(/^\s+|\s+$/g,"")=='') {
		errors.push('Please enter your first name');
		$('#fields_fname').addClass("has-error");
	}
	if ($('#fields_lname').val().replace(/^\s+|\s+$/g,"")=='') {
		errors.push('Please enter your last name');
		$('#fields_lname').addClass("has-error");
	}
	if ($('#fields_email').val().replace(/^\s+|\s+$/g,"")=='') {
		errors.push('Please enter your email address');
		$('#fields_email').addClass("has-error");
	} else if ( !emailReg.test($('#fields_email').val()) ) {
		errors.push('Please enter valid email address');
		$('#fields_email').addClass("has-error");
	}
	if ($('#fields_phone').val().replace(/^\s+|\s+$/g,"")=='') {
		errors.push('Please enter your phone number');
		$('#fields_phone').addClass("has-error");
	}
	
		
	if (errors.length == 0 ) {
		$('#loading-indicator').fadeIn(500, function(){
			$('#fields_phone').unmask();
			document.getElementById('theForm').submit();});
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
	
		//return html;
		$('body').append(html);
	
		$('#error_handler_overlay').fadeIn(500);
		return false;
	}
}


$(function() {
	$('#fields_phone').mask('000-000-0000');
	
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


</script>
</body>
</html>