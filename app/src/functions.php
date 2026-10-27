<?php

function safeRequest($strGet) {
	//$strGet = preg_replace("/[^\-a-zA-Z0-9\_]*/m","",$strGet);
	#$strGet = preg_replace("/[^a-zA-Z0-9(\040)\(\)']*/m","",$strGet); //<--to allow space \040
	$strGet = str_ireplace("javascript","",$strGet);
	$strGet = str_ireplace("encode","",$strGet);
	$strGet = str_ireplace("decode","",$strGet);
	
	return trim($strGet);
}

function getUserIpAddr() {
	return $_SERVER["HTTP_CF_CONNECTING_IP"];
}

function setPostVarsAsURL($post, $url) {
	foreach($post as $key => $value)
	{
    if ($key === array_key_first($post)) {
			$url = $url . $key . "=" . $value;
    } else {
    	$url = $url . "&" . $key . "=" . $value;
    }
	}
	
	return $url;
}



/*
 * Konnektive product id for the discounted (exit-intent) upsell, or null when
 * the discount isn't enabled/configured for the current affiliate.
 */
function get_discount_upsell_id($configs) {
	$discount = isset($configs->upsell_discount) ? $configs->upsell_discount : null;
	if (!$discount || empty($discount->enabled) || !isset($_SESSION["affid"]) || !isset($configs->affiliates[$_SESSION["affid"]])) {
		return null;
	}

	$affiliate = $configs->affiliates[$_SESSION["affid"]];
	$suffix = (isset($_SESSION["scrub"]) && $_SESSION["scrub"] === true) ? '_sc' : '';
	$discount_key = 'upsell_discount_id' . $suffix;
	if (empty($affiliate->$discount_key)) {
		return null;
	}

	// Regular rebill: charge the regular upsell product (its schedule rebills at the regular price)
	// with the discounted price sent as a price override - see add_upsell.php.
	if (!$discount->rebill_discounted) {
		$regular_key = 'upsell_id' . $suffix;
		return !empty($affiliate->$regular_key) ? $affiliate->$regular_key : null;
	}

	return $affiliate->$discount_key;
}
