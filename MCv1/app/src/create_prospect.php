<?php

	include('page_load.php');
	
	// Let user through as a honey pot
	if (!is_us_ip() || $_SESSION["anonymous_ip"] || empty($_SESSION['token']) || empty($_POST['token']) || !check_csrf_token($_SESSION['token'], $_POST['token'])) {
		// Generate fake konnektive order ID
		$permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$fake_order_id = "T" . substr(str_shuffle($permitted_chars), 0, 9);
		
		$url = $configs->path."payment.php?prospectId=".$fake_order_id;
		
		if (isset($_POST["device"]) && $_POST["device"] === "mobile") {
			$url = $configs->mobile_path."choose.php?prospectId=".$fake_order_id;
		}
		
		header('Location: ' . $url);
		return;
	}
	
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		exit();
	}
	
	if (isset($_POST["AFFID"]) && isset($configs->affiliates[$_POST["AFFID"]])) {
		$campaign_id = $configs->affiliates[$_POST["AFFID"]]->campaign_id;
	} else {
		header('Location: ' . $configs->path . '?mode=failure&err_msg=.');
		return;
	}
	
	// Save package for confirmation page
	$_SESSION["package"] = $_POST["pk"];
	
	$data = array(
		'sessionId'			=> $_SESSION["konnektive_sessionId"],
		'campaignId'		=> $configs->default_campaign_id,
		'ipAddress'			=> getUserIpAddr(),
		'firstName'			=> trim($_POST["fields_fname"]),
		'lastName'			=> trim($_POST["fields_lname"]),
		'address1'			=> trim($_POST["fields_address1"]),
		//'address2'			=> (isset($_POST['shipAddress2']) ? trim($_POST['shipAddress2']) : ''),
		'city'					=> trim($_POST["fields_city"]),
		'state'					=> trim($_POST["fields_state"]),
		'postalCode'		=> trim($_POST["fields_zip"]),
		'country'				=> trim("US"), 
		'phoneNumber'		=> trim($_POST["fields_phone"]),
		'emailAddress'	=> trim($_POST["fields_email"]),
		'shipFirstName'	=> trim($_POST["fields_fname"]),
		'shipLastName'	=> trim($_POST["fields_lname"]),
		'shipAddress1'	=> trim($_POST["fields_address1"]),
		//'shipAddress2'	=> (isset($_POST['shipAddress2']) ? trim($_POST['shipAddress2']) : ''),
		'shipCity'			=> trim($_POST["fields_city"]),
		'shipState'			=> trim($_POST["fields_state"]),
		'shipPostalCode'=> trim($_POST["fields_zip"]),
		'shipCountry'		=> "US",
		'affId'					=> trim($_SESSION["affid"]),
		'sourceValue1'	=> trim($_SESSION["c1"]),
		'sourceValue2'	=> trim($_SESSION["c2"]),
		'sourceValue3'	=> trim($_SESSION["c3"]),
		'custom4'				=> trim($_POST["notes"])
	);
	
	$resp = $konnektive->import_lead($data);
	
	$lead = json_decode($resp);
	
	if ($lead->result === "SUCCESS") {
		
		$_SESSION["shipping"] = $data;
		$_SESSION["prospectId"] = $lead->message->orderId;
		$_SESSION['customer_email'] = trim($_POST["fields_email"]);
		
		$url = $configs->path."payment.php?prospectId=".$lead->message->orderId;
		
		if (isset($_POST["device"]) && $_POST["device"] === "mobile") {
			$url = $configs->mobile_path."payment.php?prospectId=".$lead->message->orderId;
		}
		
	} else {
		// Declined
		$url = $configs->path.'?mode=failure&err_msg='. $lead->message;
		
		if (isset($_POST["device"]) && $_POST["device"] === "mobile") {
			$url = $configs->mobile_path.'shipping.php?mode=failure&err_msg='. $lead->message;
		}
	}
	
	header('Location: ' . $url);
	return;