<?php 

/*
 Modified from UPSTrack Class by Jon Whitcraft <jon_whitcraft@sweetwater.com> 
 
 upsRate class

 It's simple XML ups rate calculation class. I didn't have much time.
 enjoy.

 Chris Lee (http://www.neox.net)
 01/14/2007 - some modification on upsrate.php
 
 John Hamson (http://www.solunanet.com)
 05/14/2008  - modified class to php5

*/


//service code to service name mapping is different according to shipping 
//originting country. 
//This table is only used in service code to name conversion to show user
//Empty entry means N/A
//Ex. If shipping origin is Europe, use European Union Origin map.


/*
//From European Union Origin
$ups_service = array ( 
	'01' => '',
       	'02' => '',
	'03' => '',
	'07' => 'UPS Express',
	'08' => 'UPS Expedited',
	'11' => 'UPS Standard',
	'12' => '',
	'13' => '',
	'14' => '',
	'54' => 'UPS Worldwide Express Plus',
	'59' => '',
	'64' => 'UPS Express NA1',
	'65' => 'UPS Express Saver',
   );


//From Canada origin
$ups_service = array ( 
	'01' => 'UPS Express',
       	'02' => 'UPS Expedited',
	'03' => '',
	'07' => 'UPS Worldwide Express',
	'08' => 'UPS Worldwide Expedited',
	'11' => 'UPS Standard',
	'12' => 'UPS 3 Day Select',
	'13' => 'UPS Express Saver',
	'14' => 'UPS Express Early A.M.',
	'54' => 'UPS Worldwide Express Plus',
	'59' => '',
	'64' => '',
	'65' => '',
   );

//For other countries , please refer to UPS development docs.

*/

 
class UPS
{ 
   // You need to create userid ... at http://www.ec.ups.com    
 
    public $userid = "techiemon"; 
    public $passwd = "jahmon"; 
    public $accesskey = "3C0F18A370F10378"; 
    public $upstool='https://www.ups.com/ups.app/xml/Rate';
    public $request;  //'rate' for single service or 'shop' for all possible services
    public $service;
    public $pickuptype='06'; // 01 daily pickup
      /* Pickup Type
	01- Daily Pickup
	03- Customer Counter
	06- One Time Pickup
	07- On Call Air
	11- Suggested Retail Rates
	19- Letter Center
	20- Air Service Center
      */
    public $residential;
  //ship from location or shipper
    public $s_zip;
    public $s_state;
    public $s_country;  
  //ship to location
    public $t_zip;
    public $t_state;
    public $t_country;
  //package info
    public $package_type;  // 02 customer supplied package
    public $weight;
    public $error=0;
    public $errormsg;
    public $xmlarray = array(); 
    public $xmlreturndata = ""; 
    
  //US ORIGIN
	public  $ups_service = array ( 
		'01' => 'UPS Next Day Air',
	    '02' => 'UPS 2nd Day Air',
		'03' => 'UPS Ground',
		'07' => 'UPS Worldwide Express',
		'08' => 'UPS Worldwide Expedited',
		'11' => 'UPS Standard',
		'12' => 'UPS 3 Day Select',
		'13' => 'UPS Next Day Air Saver',
		'14' => 'UPS Next Day Air Early A.M.',
		'54' => 'UPS Worldwide Express Plus',
		'59' => 'UPS 2nd Day Air A.M.',
		'65' => 'UPS Express Saver',
	   );
 
    public function __construct($szip,$sstate,$scountry) { 
          // init function 
	      $this->s_zip = $szip;
	      $this->s_state = $sstate;
	      $this->s_country = $scountry;

    } 

    //<RequestOption>$this->request</RequestOption> 
    private function construct_request_xml(){
      $xml="<?xml version=\"1.0\"?>
			<AccessRequest xml:lang=\"en-US\">
			   <AccessLicenseNumber>$this->accesskey</AccessLicenseNumber>
			   <UserId>$this->userid</UserId>
			   <Password>$this->passwd</Password>
			</AccessRequest>
			<?xml version=\"1.0\"?>
			<RatingServiceSelectionRequest xml:lang=\"en-US\">
			  <Request>
			    <TransactionReference>
			      <CustomerContext>Rating and Service</CustomerContext>
			      <XpciVersion>1.0001</XpciVersion>
			    </TransactionReference>
			    <RequestAction>Rate</RequestAction> 
			    <RequestOption>$this->request</RequestOption> 
			  </Request>
			  <PickupType>
			 	 <Code>$this->pickuptype</Code>
			  </PickupType>
			  <Shipment>
			    <Shipper>
			      <Address>
			  		<StateProvinceCode>$this->s_state</StateProvinceCode>
				    <PostalCode>$this->s_zip</PostalCode>
				    <CountryCode>$this->s_country</CountryCode>
			      </Address>
			    </Shipper>
			    <ShipTo>
			      <Address>
				    <StateProvinceCode>$this->t_state</StateProvinceCode>
				    <PostalCode>$this->t_zip</PostalCode>
				    <CountryCode>$this->t_country</CountryCode>
				    <ResidentialAddressIndicator>$this->residential</ResidentialAddressIndicator>
			      </Address>
			    </ShipTo>
			    <Service>
			    <Code>$this->service</Code>
			    </Service>
			    <Package>
			      <PackagingType>
			        <Code>$this->package_type</Code>
			        <Description>Package</Description>
			      </PackagingType>
			      <Description>Rate Shopping</Description>
			      <PackageWeight>
			        <Weight>$this->weight</Weight>
			      </PackageWeight>
			    </Package>
			    <ShipmentServiceOptions/>
			  </Shipment>
			</RatingServiceSelectionRequest>";
      //echo $xml;
       return $xml;
    }
 
    public function rate($service='',$tzip,$tstate='',$tcountry='US', $weight,$residential=0,$packagetype='02') { 

    	if($service=='') {
        	$this->request = 'shop';
        } else {
		 	$this->request = 'rate';
        }	
       
     	$this->service = $service; 
		$this->t_zip = $tzip;
		$this->t_state= $tstate;
		$this->t_country = $tcountry;
		$this->weight = $weight;
		$this->residential=$residential;
		$this->package_type=$packagetype;
	
        $this->__runCurl(); 
        $this->xmlarray = $this->_get_xml_array($this->xmlreturndata); 

    	//check if error occurred
		if($this->xmlarray==""){
	        $this->error=0;
		  	$this->errormsg="Unable to retrieve the Shipping info";
			return NULL;
	    }

		$values = $this->xmlarray[RatingServiceSelectionResponse][Response][0];
 
		if($values[ResponseStatusCode] == 0){
		  	$this->error=$values[Error][0][ErrorCode];
		  	$this->errormsg=$values[Error][0][ErrorDescription];
	        return NULL;
        }
	
 		return $this->get_rates();

    } 

    private function __runCurl() { 
	    /** 
	    * __runCurl() 
	    * 
	    * This is processes the curl command. 
	    * 
	    * @access    private 
	    */ 
	    
		$y = $this->construct_request_xml();
	     	//echo $y;
		/* 
			// -k : do not check certification for https
		 	// -d with @ filename : read post data from the file 
			//curl -k -d @input http://www.neox.net/ups/curltest/t.php
		
		
			//generate tmp file name & save data
			$tmpfname = tempnam ("/tmp", "tmpfile");
			$fp = fopen($tmpfname,"w");
			fwrite($fp,$y);
			fclose($fp);
		
			$cmd = "curl -k -d @$tmpfname ".$this->upstool;
			$fp = popen($cmd,"r");
			while(($tmp=fread($fp,4096))!=null){
			  $output .=$tmp;
			}
			pclose($fp);
			
			unlink($tmpfname);
		
			$this->xmlreturndata = $output;
		
		        //comment rest of the lines to the end of the function
		*/

        $ch = curl_init(); 
        curl_setopt ($ch, CURLOPT_URL,"$this->upstool"); /// set the post-to url (do not include the ?query+string here!) 
        curl_setopt ($ch, CURLOPT_HEADER, 0); /// Header control 
        curl_setopt ($ch, CURLOPT_POST, 1);  /// tell it to make a POST, not a GET 
        curl_setopt ($ch, CURLOPT_POSTFIELDS, "$y");  /// put the querystring here starting with "?" 
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); /// This allows the output to be set into a variable $xyz 
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $this->xmlreturndata = curl_exec ($ch); /// execute the curl session and return the output to a variable $xyz 
        
        //echo "\n<P>:result--------------\n<P>";
		//echo $this->xmlreturndata;
        //curl_errno($ch);
        curl_close ($ch); /// close the curl session
 
    } 

    private function __get_xml_array($values, &$i) { 
    	
	    /** 
	    * __get_xml_array($values, &$i) 
	    * 
	    * This is adds the contents of the return xml into the array for easier processing. 
	    * 
	    * @access    private 
	    * @param    array    $values this is the xml data in an array 
	    * @param    int    $i    this is the current location in the array 
	    * @return    Array 
	    */ 
		    
	      $child = array(); 
	      if ($values[$i]['value']) array_push($child, $values[$i]['value']); 
	     
	      while (++$i < count($values)) 
	      { 
	        switch ($values[$i]['type']) 
	        { 
	          case 'cdata': 
	            array_push($child, $values[$i]['value']); 
	          break; 
	     
	          case 'complete': 
	            $name = $values[$i]['tag']; 
	            $child[$name]= $values[$i]['value']; 
	            if($values[$i]['attributes']) 
	            { 
	              $child[$name] = $values[$i]['attributes']; 
	            } 
	          break; 
	     
	          case 'open': 
	            $name = $values[$i]['tag']; 
	            $size = sizeof($child[$name]); 
	            if($values[$i]['attributes']) 
	            { 
	                 $child[$name][$size] = $values[$i]['attributes']; 
	                  $child[$name][$size] = $this->__get_xml_array($values, $i); 
	            } 
	            else 
	            { 
	                  $child[$name][$size] = $this->__get_xml_array($values, $i); 
	            } 
	          break; 
	     
	          case 'close': 
	            return $child; 
	          break; 
	        } 
	      } 
	      return $child; 
    } 
     
    private function _get_xml_array($data) { 
    	
	    /** 
	    * _get_xml_array($data) 
	    * 
	    * This is adds the contents of the return xml into the array for easier processing. 
	    * 
	    * @access    private 
	    * @param    string    $data this is the string of the xml data 
	    * @return    Array 
	    */ 
	    
	      $values = array(); 
	      $index = array(); 
	      $array = array(); 
	      $parser = xml_parser_create(); 
	      xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1); 
	      xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); 
	      xml_parse_into_struct($parser, $data, $values, $index); 
	      xml_parser_free($parser); 
	     
	      $i = 0; 
	     
	      $name = $values[$i]['tag']; 
	      $array[$name] = $values[$i]['attributes']; 
	      $array[$name] = $this->__get_xml_array($values, $i); 
	     
	      return $array; 
    } 
     
    public function get_rates() 
    { 
     // $retArray = array('service'=>'','basic'=>0,'option'=>0,'total'=>0,'days'=>'','time'=>'');
          
        $retArray=array();
 
        $values = $this->xmlarray[RatingServiceSelectionResponse][RatedShipment];
      	$ct = count($values);
        for($i=0;$i<$ct;$i++){
	  $current=&$values[$i];

          $retArray[$i]['service'] = $current[Service][0][Code]; 
          $retArray[$i]['basic'] = $current[TransportationCharges][0][MonetaryValue];
	  $retArray[$i]['option'] = $current[ServiceOptionsCharges][0][MonetaryValue];
	  $retArray[$i]['total'] = $current[TotalCharges][0][MonetaryValue];
	  $retArray[$i]['days'] =  $current[GuaranteedDaysToDelivery];
	  $retArray[$i]['time'] = $current[ScheduledDeliveryTime];
        }

        unset($values); 
         
        return $retArray; 
    } 

} 

/*
//example
$ups = new UPS('01040','MA','US');

//function rate($service='',$tzip,$tstate='',$tcountry='US',$weight,$residential=0,$packagetype='02') 
// if first arg is empty, show all possible services
$ok = $ups->rate('','56093','MN','US',33.2);
//else use service code such as '03' for ground
$data = $ups->xmlarray;   
//print_r($data);
print_r($ups->get_rates());

*/


?>
