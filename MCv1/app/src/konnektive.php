<?php

class KonnektiveApi {
	
	private $loginId;
	private $password;
	
	public function __construct($loginId, $password) {
		$this->loginId = $loginId;
		$this->password = $password;
	}
	
	public function import_click($data) {
		return $this->post_request($data, 'https://api.konnektive.com/landers/clicks/import/');
	}
	
	public function import_lead($data) {
		return $this->post_request($data, 'https://api.konnektive.com/leads/import/');
	}
	
	public function import_order($data) {
		return $this->post_request($data, 'https://api.konnektive.com/order/import/');
	}
	
	public function import_upsale($data) {
		return $this->post_request($data, 'https://api.konnektive.com/upsale/import/');
	}
	
	public function query_order($data) {
		return $this->post_request($data, 'https://api.konnektive.com/order/query/');
	}
	
	public function confirm_order($data) {
		return $this->post_request($data, 'https://api.konnektive.com/order/confirm/');
	}
	
	public function get_order($orderId) {
		$resp = $this->post_request(array(
			'orderId' => $orderId
		), 'https://api.konnektive.com/order/query/');
	
		$resp = json_decode($resp);
		
		if ($resp->result === "SUCCESS") {
			return $resp->message->data[0];
		} else {
			return null;
		}
	}
	
	public function get_campaign($campaignId) {
		$resp = $this->post_request(array(
			'campaignId' => $campaignId
		), 'https://api.konnektive.com/campaign/query/');
		
		$resp = json_decode($resp);
		
		if ($resp->result === "SUCCESS") {
			return $resp->message->data->$campaignId;
		} else {
			return null;
		}
	}
	

	public function post_request($data, $url) {
		
		$i = 0;
		foreach($data as $key => $value) {
			if ($i === 0) { // first iteration
				$url = $url."?".$key."=".urlencode((string)$value);
			} else {
				$url = $url."&".$key."=".urlencode((string)$value);
			}
			$i++;
		}
		
		$url = $url."&loginId=".$this->loginId."&password=".$this->password;
			
		$curl = curl_init();
		
		curl_setopt_array($curl, [
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
			CURLOPT_POST => 1,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
			  'Accept: application/json'
			)
		]);
				
		$resp = curl_exec($curl);
		$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if (is_resource($curl)) {
			@curl_close($curl);
		}
				
		return $resp;
	}
	
}