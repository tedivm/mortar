<?php


class InfoRegistry
{
	protected $User;
	protected $Configuration;
	protected $Site;
	protected $Post;
	protected $Get;
	protected $Runtime;

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
					$this->Site = ActiveSite::getSite();
					break;
				case 'Input':
					$this->Post = Input::getInput();
					break;
				case 'Query':
					$this->Query = Query::getQuery();
					break;
			//	case 'Runtime':
			//		$this->Runtime = RuntimeConfig::getInstance();
			//		break;
				default:
					break;
			}
		}

		return (isset($this->$offset)) ? $this->$offset : false;
	}
}



?>