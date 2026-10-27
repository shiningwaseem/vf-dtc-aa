<?php

/**
 * StickyLegacy
 *
 * This class interfaces with the Sticky.io legacy API.  
 * You can find the API documentation here https://developer-legacy-prod.sticky.io/
 * 
 */
	
class StickyLegacy {
	
	private $username;
	private $password;
	private $domain;
	
	private $endpoints = array(
		"membership"	=> "/admin/membership.php",
		"transact"		=> "/admin/transact.php"
	);
	
	public function __construct($domain, $username, $password) {
		$this->domain = $domain;
		$this->username = $username;
		$this->password = $password;
	}
	
	/*
	 * GET Validate Credentials
	 * https://developer-legacy-prod.sticky.io/#ecca7530-9ae0-40b6-b7f7-5c824ace40ed
	 * @return boolean
	 */
	public function validate_credentials() {
		$resp = $this->curl_request("GET", $this->endpoints["membership"], "validate_credentials", array());
		return $resp === '100';
	}
	
	/*
	 * GET New Prospect
	 * https://developer-legacy-prod.sticky.io/#9b59fd8b-7bd9-4fa2-a7a4-553f78b2b779
	 */
	public function new_prospect(Array $fields) {
		$resp = $this->curl_request("GET", $this->endpoints["transact"], "NewProspect", $fields);
		
		// Check if order returned as a string
		parse_str($resp, $resp);
		
		return $resp;
	}
	
	/*
	 * GET NewOrderWithProspect
	 * https://developer-legacy-prod.sticky.io/#cabf3350-fe7f-44cd-92ca-ac4695777830
	 */
	public function new_order_with_prospect(Array $fields) {
		$resp = $this->curl_request("GET", $this->endpoints["transact"], "NewOrderWithProspect", $fields);
		
		// Check if order returned as a string
		parse_str($resp, $resp);
		
		return $resp;
	}
	
	/*
	 * GET NewOrderCardOnFile
	 * https://developer-legacy-prod.sticky.io/#409abc19-5671-4f38-b51a-50a53af0e181
	 */
	public function new_order_card_on_file(Array $fields) {
		$resp = $this->curl_request("GET", $this->endpoints["transact"], "NewOrderCardOnFile", $fields);
		
		// Check if order returned as a string
		parse_str($resp, $resp);
		
		return $resp;
	}
	
	/*
	 * GET NewOrder
	 * https://developer-legacy-prod.sticky.io/#b99394cd-cb8c-4856-9c5b-efdb452f95b5
	 */
	public function new_order(Array $fields) {
		$resp = $this->curl_request("POST", $this->endpoints["transact"], "NewOrder", $fields);
		
		// This endpoint is returned as a string
		parse_str($resp, $resp);
		
		return $resp;
	}
	
	/*
	 * Sends curl request to Sticky.io API
	 */
	private function curl_request(String $type, String $endpoint, String $method, Array $fields) {
		
		$url = $this->domain . $endpoint . "?username=".$this->username."&password=".$this->password."&method=".$method;
			
		foreach($fields as $key => $value) {
			$url = $url."&".$key."=".urlencode($value);
		}
					
		$curl = curl_init();
		
		curl_setopt_array($curl, [
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
			  'Accept: application/json'
			)
		]);
		
		/*
		 * Add CURLOPT_POSTFIELDS automatically 
		 * changes the request from GET to POST.
		 */		
		if ($method === "POST") {
			curl_setopt_array($curl, CURLOPT_POSTFIELDS, $fields);
		}
				
		$resp = curl_exec($curl);
		$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		curl_close($curl);
				
		return $resp;
	}


}