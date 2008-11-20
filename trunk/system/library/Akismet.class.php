<?php
/*
Copyright Robert Hafner

*/
include_once('../third_party/microakismet.class.php');

class Akismet
{
	private $is_spam = false;
	private $micro_akismet;
	
	
	public function __construct()
	{
		$config = Config::getInstance();		
		$this->micro_akismet = new MicroAkismet( $config->setting('akismet_api_key'), $config->setting('akismet_blog'), $user_agent );
	}
	
	
	
	
	
}


?>