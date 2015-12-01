<?php
	/*
	 *
	 *	HubSync PHP Script
	 *	Copyright 2015 - Taylor Networks
	 *	
	 *	Licensed under the MIT license, see "LICENSE" for more information.
	 *
	 */
	 
	include_once('src/IpUtils.php');
	use Symfony\Component\HttpFoundation;
	
	// Configuration options
	$apiEndpoint    = "https://api.github.com/"; // Change for enterprise deployments
	$apiUserAgent   = "YourGithubUsername"; // Used to set the User-Agent for GitHub API requests. See https://developer.github.com/v3/#user-agent-required for more info.
	$clientIpHeader = "REMOTE_ADDR"; // Change if running behind CDN (e.g. Cloudflare or MaxCDN). Consult your CDN documentation for the correct header parameters.
	
	// Variables
	$responseCode;
	$responseText;
	
	// Detect and verify client IP address
	$clientIp = "192.168.0.1"; //$_SERVER[$clientIpHeader];
	$apiPath  = "meta";
	
	$curl = curl_init($apiEndpoint . $apiPath);
	
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_USERAGENT, $apiUserAgent);
	
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
			$clientMatches = false;
			
			$ipv4Sources = array();
			$ipv6Sources = array();
			
			$response = json_decode($curl_response);
			
			var_dump($curl_response);
			
			foreach($response->hooks as $ipSource)
			{
				if(filter_var($ipSource, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
				{
					array_push($ipv6Sources, $ipSource);
				}
				elseif(filter_var($ipSource, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
				{
					array_push($ipv4Sources, $ipSource);
				}
			}
			
			if(filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
			{
				$clientMatches = IpUtils::checkIp($clientIp, $ipv6Sources);
			}
			else
			{
				$clientMatches = IpUtils::checkIp($clientIp, $ipv4Sources);
			}
			
			if($clientMatches)
			{
				
			}
			else
			{
				$responseCode = 403;
				$responseText = "Client IP is not in API provided subnets";
			}
			
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
