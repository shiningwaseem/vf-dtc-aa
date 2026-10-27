<?php

	include('../../_global.php');

	// Don't let customer attempt to purchase upsell again.
	if ($_SESSION['upsell_count'] == 4 || $_SESSION["added_upsell"] === false || $_SESSION["add_upsell2"] === false || $_SESSION["add_upsell3"] === false) {
		echo json_encode([
			'status' => 'Failed',
			'customer_id' => $_POST["customer_id"],
			'upsell' => false,
			'url' => $configs->path.'confirmation.php?customerId='.$_POST["customer_id"]."&upsell=false"
		], 400);
	}
	
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		http_response_code(404);
		exit();
	}
	
	$resp = $konnektive->get_order($_SESSION["orderId"]);
		
	if (is_null($resp)) {
		echo "No order associated with customer.";
		return;
	}
	
	// 3DS
	if ($configs->threeds_enabled) {
		if (isset($_POST["cavv"]) && $_POST["cavv"] !== "") {
			$data["cavv"] = $_POST["cavv"];
			$data["xid"] = $_POST["ds_trans_id"];
			$data["eci"] = $_POST["eci"];
		}
	}

	$upsell_product_id = '';

	if (isset($_POST["add_upsell"]) && !empty($_POST["add_upsell"])){
		// Upsell 1
		if ($_POST["add_upsell"] == 1) {
			$upsell_product_id = $configs->affiliates[$_SESSION["affid"]]->upsell_id; // Set product ID
		}

		// Upsell 2
		if ($_POST["add_upsell"] == 2) {
			$upsell_product_id = $configs->affiliates[$_SESSION["affid"]]->upsell_id2; // Set product ID
		}

		// Upsell 3
		if ($_POST["add_upsell"] == 3) {
			$upsell_product_id = $configs->affiliates[$_SESSION["affid"]]->upsell_id3; // Set product ID
		}

		// Upsell 4
		if ($_POST["add_upsell"] == 4) {
			$upsell_product_id = $configs->affiliates[$_SESSION["affid"]]->upsell_id4; // Set product ID
		}
	}
	
	if (isset($_SESSION["scrub"]) && $_SESSION["scrub"] === true) {
		if (isset($_POST["add_upsell"]) && !empty($_POST["add_upsell"])){
			// Upsell 1
			if ($_POST["add_upsell"] == 1) {
				$upsell_product_id = $configs->affiliates[$_SESSION["affid"]]->upsell_id_sc; // Set product ID
			}
			
			// Upsell 2
			if ($_POST["add_upsell"] == 2) {
				$upsell_product_id = $configs->affiliates[$_SESSION["affid"]]->upsell_id2_sc; // Set product ID
			}
	
			// Upsell 3
			if ($_POST["add_upsell"] == 3) {
				$upsell_product_id = $configs->affiliates[$_SESSION["affid"]]->upsell_id3_sc; // Set product ID
			}

			// Upsell 4
		if ($_POST["add_upsell"] == 4) {
			$upsell_product_id = $configs->affiliates[$_SESSION["affid"]]->upsell_id4_sc; // Set product ID
		}
		}
	}
	
	// Exit-intent discount: only honoured if upsell.php actually offered it this session.
	$upsell_price = $configs->upsell_price;
	if (isset($_POST["add_upsell"]) && $_POST["add_upsell"] == 1 && isset($_POST["upsell_offer"]) && $_POST["upsell_offer"] === 'discount' && !empty($_SESSION["upsell_discount_offered"])) {
		$discount_product_id = get_discount_upsell_id($configs);
		if ($discount_product_id) {
			$upsell_product_id = $discount_product_id;
			$upsell_price = $configs->upsell_discount->price;
			$upsell_price_override = !$configs->upsell_discount->rebill_discounted;
		}
	}

	$_SESSION['customer_id'] = $_POST["customer_id"];
// /******************************* New Change ********************************/
// 		$postData = array(
// 						'offer_url'    => 'ErextremeVigor',
// 						'order_id'     => $_SESSION["orderId"],
// 						'product_id'   => $upsell_product_id
// 						);
		
		
// 		/*$postData = array(
// 					'offer_url'    => 'Cypher Diet Gummies',
// 					'order_id'     => 'sadsa4sa',
// 					'product_id'   => 12345,
// 					);*/
	
	
// 		$url = 'https://sysmods.com/index.php?req=user/UpsellDataCapture';
// 		$postData = $postData;
// 		$ch = curl_init();
// 		curl_setopt($ch, CURLOPT_URL, $url);
// 		curl_setopt($ch, CURLOPT_POST, 1);
// 		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
// 		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Set this option to capture the response
// 		$response = curl_exec($ch);
// 		if ($response === false) {
// 			echo 'cURL error: ' . curl_error($ch);
// 		}
// 		curl_close($ch);
// 		$resp = json_decode($response);
		
// 		if ($resp->status === "success") {
// 			$_SESSION["total"] = $_SESSION["total"] + $configs->upsell_price;
// 			if (isset($_POST["add_upsell"]) && $_POST["add_upsell"] == true) {
// 				$_SESSION["added_upsell"] = true;
// 			}
// 			if (isset($_POST["add_upsell2"]) && $_POST["add_upsell2"] == true) {
// 				$_SESSION["added_upsell2"] = true;
// 			}
// 			echo json_encode([
// 				'status' => 'Success',
// 				'customer_id' => $_POST["customer_id"],
// 				'upsell' => true,
// 				'order_id'     => $_SESSION["orderId"],
// 				'product_id'   => $upsell_product_id && '135',
// 			], 200);
// 		}else{
// 			$_SESSION["added_upsell2"] = false;
// 			echo json_encode([
// 				'status' => 'Failed',
// 				'customer_id' => $_POST["customer_id"],
// 				'upsell' => false
// 			], 400);
// 		}	
// 	/*********************** end *************************************************************************/
	
	$upsale_data = array(
		"orderId" 	=> $_SESSION["orderId"],
		"productId" => $upsell_product_id
	);
	if (!empty($upsell_price_override)) {
		$upsale_data["productPrice"] = $upsell_price;
	}
	$resp = $konnektive->import_upsale($upsale_data);
	
	$resp = json_decode($resp);
	
	
	if ($resp->result === "SUCCESS") {
		$_SESSION["total"] = $_SESSION["total"] + $upsell_price;
		$_SESSION["upsell_price_charged"] = $upsell_price;
		$_SESSION['upsell_count']++;

		if (isset($_POST["add_upsell"]) && !empty($_POST["add_upsell"])){
			// Upsell 1
			if ($_POST["add_upsell"] == 1) {
				$_SESSION["added_upsell"] = true;
			}
	
			// Upsell 2
			if ($_POST["add_upsell"] == 2) {
				$_SESSION["added_upsell2"] = true;
			}
	
			// Upsell 3
			if ($_POST["add_upsell"] == 3) {
				$_SESSION["added_upsell3"] = true;
			}

			// Upsell 4
			if ($_POST["add_upsell"] == 4) {
				$_SESSION["added_upsell4"] = true;
			}
		}
		
		echo json_encode([
			'status' => 'Success',
			'customer_id' => $_POST["customer_id"],
			'upsell' => true,
			'upsell_count' => $_SESSION['upsell_count'],
		], 200);
	} else {
		if (isset($_POST["add_upsell"]) && !empty($_POST["add_upsell"])){
			// Upsell 1
			if ($_POST["add_upsell"] == 1) {
				$_SESSION["added_upsell"] = false;
			}
	
			// Upsell 2
			if ($_POST["add_upsell"] == 2) {
				$_SESSION["added_upsell2"] = false;
			}
	
			// Upsell 3
			if ($_POST["add_upsell"] == 3) {
				$_SESSION["added_upsell3"] = false;
			}

			// Upsell 4
			if ($_POST["add_upsell"] == 4) {
				$_SESSION["added_upsell4"] = false;
			}
		}
		
		echo json_encode([
			'status' => 'Failed',
			'customer_id' => $_POST["customer_id"],
			'upsell' => false,
		], 400);
	}

	exit(0);
	