<?php

class psuedotabnotesthinger
{
	
	
	
	
	
	
	
	
	
	
}





class AdminTab
{
	
	public $tabLocations = array();
	
	public function __construct($name, $siteId = 0)
	{
		$config = Config::getInstance();
		if($siteId = 0)
			$siteId = $config['siteId'];
			
			
		$siteLocation = new Location($siteLocationId);
		$tabLocations = $siteLocation->getChildren('tab');
		
		foreach($tabLocations as $tab){
			$this->tabLocations[$tab->name] = $tab;
		}
			
	}
	
	public function getSidebar($name)
	{
		
	}
	
}

class AdminTabSpecific
{
	public $location;
	public $name;
	
	public function __construct($locationId)
	{
		$location = new Location($locationId);
		$this->name = $location->name;
	}
	
	public function getSidebar()
	{
		$hooks = new Hook($this->location->id, 'AdminSidebar');
	}
	
	
}


?>