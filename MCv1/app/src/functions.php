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

/**
 * Helper to check if a card number matches any card in a configured list
 */
function is_test_card_match($cardNumber, $cardList) {
	if (empty($cardNumber) || empty($cardList)) return false;
	$clean = preg_replace('/\D/', '', (string)$cardNumber);
	foreach ((array)$cardList as $item) {
		$cleanItem = preg_replace('/\D/', '', (string)$item);
		if (!empty($cleanItem) && ($clean === $cleanItem || strpos($clean, $cleanItem) === 0)) {
			return true;
		}
	}
	return false;
}

/**
 * Modular helper: Check if a card is Mastercard by number prefix or card type
 * Supports live production cards as well as configured test_cards (generic, visa, master)
 *
 * @param string $cardNumber
 * @param string $cardType
 * @return bool
 */
function is_mastercard($cardNumber = '', $cardType = '') {
	global $configs;
	$cleanCard = preg_replace('/\D/', '', $cardNumber);

	if (!empty($cleanCard)) {
		// Check against configured test cards in config.php
		$testCards = $configs->mc_promo->test_cards ?? null;
		if ($testCards) {
			if (!empty($testCards->master) && is_test_card_match($cleanCard, $testCards->master)) {
				return true;
			}
			if (!empty($testCards->visa) && is_test_card_match($cleanCard, $testCards->visa)) {
				return false;
			}
			if (!empty($testCards->generic) && is_test_card_match($cleanCard, $testCards->generic)) {
				$type = strtolower(trim((string)$cardType));
				return in_array($type, array('master', 'mastercard', 'mc'));
			}
		}

		// Support Konnektive test cards starting with '7' (e.g. 74... for Visa, 75... for Mastercard)
		$effectiveCard = (strpos($cleanCard, '7') === 0 && strlen($cleanCard) > 1) ? substr($cleanCard, 1) : $cleanCard;

		// When card number is provided (and not generic test card 0000), card digits are the absolute single source of truth.
		// Visa (4...), Amex (3...), Discover (6...) will strictly return false.
		if (strpos($cleanCard, '0000') !== 0) {
			return (
				preg_match('/^5[1-5]/', $effectiveCard) ||
				preg_match('/^2(22[1-9]|2[3-9]|[3-6]\d|7[0-1]\d|720)/', $effectiveCard)
			) ? true : false;
		}
	}
	$type = strtolower(trim((string)$cardType));
	return in_array($type, array('master', 'mastercard', 'mc'));
}

/** Calculate configured initial price with optional Mastercard discount. */
function get_mc_package_price($configs, $package, $isMastercard = false) {
	$package = (int) $package;
	if (in_array($package, array(5, 6), true)) {
		$price = (float) ($configs->bottle_price_5 ?? 0);
	} elseif (in_array($package, array(3, 4), true)) {
		$price = (float) ($configs->bottle_price_3 ?? 0);
	} else {
		$price = (float) ($configs->bottle_price_1 ?? 0);
	}
	$discountEnabled = !empty($configs->mc_promo->price_discount_enabled);
	$discountAmount = max(0, (float) ($configs->mc_promo->price_discount_amount ?? 20));
	if ($isMastercard && $discountEnabled) {
		$price = max(0, $price - $discountAmount);
	}
	return round($price, 2);
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
