<?php

class TagBoxEnv
{

	protected $site;
	protected $user;

	public function __construct ()
	{
		if(defined('INSTALLMODE') && INSTALLMODE === true)
			return;

		$this->site = ActiveSite::getSite();
		$this->user = ActiveUser::getUser();
	}

	public function __get($tagname)
	{
		switch($tagname) {
			case "siteLink":

				if(!isset($this->site))
					return BASE_URL;

				return $this->site->getUrl();
			case "siteName":

				if(!isset($this->site))
					return 'Mortar Installation';

				return str_replace('_', ' ', $this->site->getLocation()->getName());
			case "user":
				return $this->user['name'];
			default:
				return false;
		}
	}

	public function __isset($tagname)
	{
		switch($tagname) {
			case "siteLink":
			case "siteName":
			case "user":
				return true;
			default:
				return false;
		}
	}
}

?>
