<?php


class InfoRegistry
{
	protected $User;
	protected $Configuration;
	protected $Site;
	
	static $instance;
	
	private function __construct()
	{
		
	}
	
	public static function getInstance()
	{
		if(!isset(self::$instance)){
			$object = __CLASS__;
			self::$instance = new $object;			
		}
		return self::$instance;
	}
	
	
	
	protected function __get($offset)
	{
		
		if(!isset($this->$offset))
		{
			switch ($offset) {
				case 'User':
					$this->User = ActiveUser::getInstance();
					break;
				case 'Configuration':
					$this->Configuration = Config::getInstance();
					break;
				case 'Site':
					$this->Site = ActiveSite::get_instance();
					break;
									
				default:
					break;
			}
		}
		
		
		return $this->$offset;
	}
}



?>