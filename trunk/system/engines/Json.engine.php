<?php

class JsonEngine extends Engine 
{
	public $engine_type = 'Json';
		

	public $default_action = 'main_display';
	
	protected function StartEngine()
	{
		session_start();
		$config = Config::getInstance();		
	}
	
	protected function processAction($actionResults)
	{
		$this->content = json_encode($actionResults);
	}
	
	public function finish()
	{
		session_commit();
		parent::finish();
	}
	
}


?>