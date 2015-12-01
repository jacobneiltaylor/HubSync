<?php
	/*
	 *
	 *	HubSync PHP Script
	 *	Copyright 2015 - Taylor Networks
	 *	
	 *	Licensed under the MIT license, see "LICENSE" for more information.
	 *
	 */
	 
	include_once('lib/IpUtils.php');
	use Symfony\Component\HttpFoundation\IpUtils;
	
	// Configuration options
	$apiEndpoint    = "https://api.github.com/"; // Change for enterprise deployments
	$apiUserAgent   = "YourGithubUsername"; // Used to set the User-Agent for GitHub API requests. See https://developer.github.com/v3/#user-agent-required for more info.
	$secureMode     = FALSE; // Set to true to cryptographically validate webhook request payloads with $requestToken.
	$requestToken   = "superSecretToken"; // This is the shared secret used to verify webhook request payloads. Change this before using this script
	$clientIpHeader = "REMOTE_ADDR"; // Change if running behind CDN (e.g. Cloudflare or MaxCDN). Consult your CDN documentation for the correct header parameters.
	
	// Variables
	$responseCode;
	$responseText;
	$payload;
	
	// Detect and verify client IP address
	$clientIp = $_SERVER[$clientIpHeader];
	$apiPath  = "meta";
	
	if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST")
	{
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
				
				foreach($response->hooks as $ipSource)
				{
					$ipSourceStripped = explode('/', $ipSource)[0];
					
					if(filter_var($ipSourceStripped, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
					{
						array_push($ipv6Sources, $ipSource);
					}
					elseif(filter_var($ipSourceStripped, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
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
					$payload = file_get_contents('php://input');
					
					$requestValid = FALSE;
					$authErrorText = "Unspecified error";
					
					if($secureMode)
					{
						if(isset($_SERVER['HTTP_X_HUB_SIGNATURE']))
						{
							$headerSig = $_SERVER['HTTP_X_HUB_SIGNATURE'];
							list($algo, $sig) = explode('=', $headerSig, 2);
							
							$payloadHash = hash_hmac($algo, $payload, $requestToken);
							
							if($payloadHash === $sig)
							{
								$requestValid = TRUE;
							}
							else
							{
								$authErrorText = "signature invalid";
							}
							
						}
						else
						{
							$authErrorText = "GitHub signature request header missing";
						}
					}
					else
					{
						$requestValid = TRUE;
					}
					
					if($requestValid)
					{
						$responseCode = 405;
						$responseText = "Event functionality unimplemented";
						
						$event = $_SERVER['HTTP_X_GITHUB_EVENT'];
						
						switch($event)
						{
							case "push":
								//Do something here
								$responseCode = 202;
								$responseText = "You have sent a push webhook!";
								break;
								
							case "ping":
								$responseCode = 202;
								$responseText = "Ping received successfully";
								break;
						}
					}
					else
					{
						$responseCode = 401;
						$responseText = "Request authentication failed - " . $authErrorText;					
					}
				}
				else
				{
					$responseCode = 403;
					$responseText = "Client IP is not in an authorized subnet";
				}
				
			}
			else
			{
				$responseCode = 500;
				$responseText = "Invalid client IP supplied to engine";
			}
		}
	}
	else
	{
		$responseCode = 405;
		$responseText = "This API only accepts POST requests";
	}
	
	header('Content-type: application/json');
	http_response_code($responseCode);
	
	$responseCode = json_encode($responseCode);
	$responseText = json_encode($responseText) . "\n";
?>

{
	"responseCode": <?php echo $responseCode; ?>,
	"responseText": <?php echo $responseText; ?>
}