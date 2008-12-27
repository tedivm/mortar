<?php

class Get extends Post
{
	public $variables = array();
	static $instance;

	private function __construct()
	{
		$this->variables = get_magic_quotes_gpc() ? array_map('stripslashes', $_GET) : $_GET;
	}

	public function addValues($values)
	{
		if(!is_array($values))
			return;

		foreach($values as $index => $value)
		{
			$this->variables[$index] = $value;
		}
	}

	public static function getInstance()
	{
		if(!isset(self::$instance)){
			$object = __CLASS__;
			self::$instance = new $object();
		}
		return self::$instance;
	}
}


?>