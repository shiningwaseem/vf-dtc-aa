<?php

	include('./page_load.php');

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		http_response_code(404);
		exit();
	}
	
	$url = $configs->mobile_path . 'choose.php?';
	
	foreach($_POST as $key => $value)
	{
    if ($key === array_key_first($_POST)) {
			$url = $url . $key . "=" . $value;
    } else {
    	$url = $url . "&" . $key . "=" . $value;
    }
	}
	
	header('Location:' . $url);
