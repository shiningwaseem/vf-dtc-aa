<?php
	
function create_click(Array $data) {
	return post_request(
		'https://commsqrd-datapipe.com/api/click',
		$data
	);
}

function anonymous_ip($ip_info) {
	return post_request('https://anti-fraud.commercesqrd.com/', $ip_info);
}


// Sticky API only uses POST requests and uses basic authentication
function post_request(String $url, Array $fields = array()) {

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_POSTFIELDS => json_encode($fields),
				CURLOPT_CONNECTTIMEOUT => 1,
				CURLOPT_TIMEOUT => 2,
				CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Accept: application/json'
        ),
        CURLOPT_USERNAME => "",
        CURLOPT_PASSWORD => ""
    ]);

    $resp = curl_exec($curl);
    //$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if (is_resource($curl)) {
        @curl_close($curl);
    }

    return $resp;
}