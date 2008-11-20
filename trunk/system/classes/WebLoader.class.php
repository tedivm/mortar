<?php

class load
{
	public $url;
	public $post = array();
	public $get = array();
	
	/*
		TODO:
			unfunction the function- split things out into the class
			
			allow for both get and post in the same function
			
			
	*/
	
	protected function load($url,$options=array('method'=>'get','return_info'=>false)) {
		
		$url_parts = parse_url($url);
		$info = array(//Currently only supported by curl.
			'http_code'    => 200
		);
		$response = '';
		$send_header = array(
			'Accept' => 'text/*',
			'User-Agent' => 'BinGet/1.00.A (http://www.bin-co.com/php/scripts/load/)'
		);
	
		///////////////////////////// Curl /////////////////////////////////////
		//If curl is available, use curl to get the data.
		if(function_exists("curl_init") and (!(isset($options['use']) and $options['use'] == 'fsocketopen'))) { //Don't user curl if it is specifically stated to user fsocketopen in the options

			if(isset($options['method']) and $options['method'] == 'post') {
				$page = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];
			}else{
				$page = $url;
			}
	        
			$ch = curl_init($url_parts['host']);
	
			curl_setopt($ch, CURLOPT_URL, $page);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //Just return the data - not print the whole thing.
			curl_setopt($ch, CURLOPT_HEADER, true); //We need the headers
			curl_setopt($ch, CURLOPT_NOBODY, false); //The content - if true, will not download the contents
	        
			if(isset($options['method']) and $options['method'] == 'post' and $url_parts['query']) {
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $url_parts['query']);
			}
	        
			//Set the headers our spiders sends
			curl_setopt($ch, CURLOPT_USERAGENT, $send_header['User-Agent']); //The Name of the UserAgent we will be using ;)
			$custom_headers = array("Accept: " . $send_header['Accept'] );
			
			if(isset($options['modified_since']))
				array_push($custom_headers,"If-Modified-Since: ".gmdate('D, d M Y H:i:s \G\M\T',strtotime($options['modified_since'])));
	        
			curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);
			curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt"); //If ever needed...
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	
			if(isset($url_parts['user']) and isset($url_parts['pass'])) {
				$custom_headers = array("Authorization: Basic ".base64_encode($url_parts['user'].':'.$url_parts['pass']));
				curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);
			}
	
			$response = curl_exec($ch);
			$info = curl_getinfo($ch); //Some information on the fetch
			curl_close($ch);
	
		//////////////////////////////////////////// FSockOpen //////////////////////////////
		}else{ //If there is no curl, use fsocketopen
			if(isset($url_parts['query'])) {
				if(isset($options['method']) and $options['method'] == 'post')
				{
					$page = $url_parts['path'];
				}else{
					$page = $url_parts['path'] . '?' . $url_parts['query'];
				}
			}else{
				$page = $url_parts['path'];
			}
	
			$fp = fsockopen($url_parts['host'], 80, $errno, $errstr, 30);
			if($fp)
			{
				$out = '';
				if(isset($options['method']) and $options['method'] == 'post' and isset($url_parts['query'])) 
				{
					$out .= "POST $page HTTP/1.1\r\n";
				}else{
					$out .= "GET $page HTTP/1.0\r\n"; //HTTP/1.0 is much easier to handle than HTTP/1.1
				}
	            
				$out .= "Host: $url_parts[host]\r\n";
				$out .= "Accept: $send_header[Accept]\r\n";
				$out .= "User-Agent: {$send_header['User-Agent']}\r\n";
	            
				if(isset($options['modified_since']))
					$out .= "If-Modified-Since: ".gmdate('D, d M Y H:i:s \G\M\T',strtotime($options['modified_since'])) ."\r\n";
	
				$out .= "Connection: Close\r\n";
	            
				//HTTP Basic Authorization support
				if(isset($url_parts['user']) and isset($url_parts['pass'])) 
				{
					$out .= "Authorization: Basic ".base64_encode($url_parts['user'].':'.$url_parts['pass']) . "\r\n";
				}
	
				//If the request is post - pass the data in a special way.
	            
				if(isset($options['method']) and $options['method'] == 'post' and $url_parts['query']) 
				{
					$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
					$out .= 'Content-Length: ' . strlen($url_parts['query']) . "\r\n";
					$out .= "\r\n" . $url_parts['query'];
				}
	            
				$out .= "\r\n";
				fwrite($fp, $out);
	            
				while (!feof($fp)) 
				{
					$response .= fgets($fp, 128);
				}
				fclose($fp);
			}
		}
	
	    
		//Get the headers in an associative array
	    
		$headers = array();
	
		if($info['http_code'] == 404) {
			$body = "";
			$headers['Status'] = 404;
		}else{
	        
			//Seperate header and content
			$separator_position = strpos($response,"\r\n\r\n");
			$header_text = substr($response,0,$separator_position);
			$body = substr($response,$separator_position+4);
			
			foreach(explode("\n",$header_text) as $line) 
			{
				$parts = explode(": ",$line);
				if(count($parts) == 2) $headers[$parts[0]] = chop($parts[1]);
			}
		}
	
		if($options['return_info']) return array('headers' => $headers, 'body' => $body, 'info' => $info);
		return $body;
	}


}

?>