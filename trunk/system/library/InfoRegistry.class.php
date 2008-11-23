<?php


class InfoRegistry
{
	protected $User;
	protected $Configuration;
	protected $Site;
	protected $Post;
	protected $Get;
	
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
				case 'Post':
					$this->Post = Post::getInstance();
					break;
				case 'Get':
					$this->Get = Get::getInstance();
					break;					
					
				default:
					break;
			}
		}
		
		
		return $this->$offset;
	}
}



?>