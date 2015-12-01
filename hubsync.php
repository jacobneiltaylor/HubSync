<?php
	/*
	 *
	 *	HubSync PHP Script
	 *	Copyright 2015 - Taylor Networks
	 *	
	 *	Licensed under the MIT license, see "LICENSE" for more information.
	 *
	 */
	 
	include_once('src/IpUtils.php')
	
	use Symfony\Component\HttpFoundation;
	
	// Configuration options
	$apiEndpoint    = "https://api.github.com/"; // Change for enterprise deployments
	$clientIpHeader = "REMOTE_ADDR"; // Change if running behind CDN (e.g. Cloudflare or MaxCDN). Consult your CDN documentation for the correct header name.
	
	// Variables
	$responseCode;
	$responseText;
	
	// Detect and verify client IP address
	$clientIp = "192.168.0.1"; //$_SERVER[$clientIpHeader];
	$apiPath  = "meta";
	
	$curl = curl_init($apiEndpoint . $apiPath);
	
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	
	$curl_response = curl_exec($curl);
	
	if ($curl_response === false) 
	{
    	$info = curl_getinfo($curl);
    	$responseCode = 500;
		$responseText = "cURL encountered an error while attempting to access API - " . curl_error($curl);
		curl_close($curl);
	}
	else
	{
		if((bool) filter_var($clientIp, FILTER_VALIDATE_IP))
		{
			
		}
		else
		{
			$responseCode = 500;
			$responseText = "Invalid client IP supplied to script";
		}
	}
	
	http_response_code($responseCode);
	
	$responseCode = json_encode($responseCode);
	$responseText = json_encode($responseText) . "\n";
?>

{
	"responseCode": <?php echo $responseCode; ?>,
	"responseText": <?php echo $responseText; ?>
}
