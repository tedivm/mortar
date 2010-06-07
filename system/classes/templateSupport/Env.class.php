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

	protected function loginLink()
	{
		$currentUrl = Query::getUrl();
		$location = $currentUrl->locationId;
		$action = $currentUrl->action;
		$module = $currentUrl->module;

		if(isset($action) && ($action == 'LogIn' || $action == 'LogOut')) {
			$location = $currentUrl->l;
			$action = $currentUrl->a;
			$module = $currentUrl->m;
		}

		$url = new Url();
		$url->module = 'Mortar';
		if($this->user['name'] === 'Guest') {
			$url->action = 'LogIn';
		} else {
			$url->action = 'LogOut';
		}

		if(isset($action))
			$url->a = $action;
		if(isset($location))
			$url->l = $location;
		if(isset($module))
			$url->m = $module;

		$a = new HtmlObject('a');
		$a->addClass('login_link')->
			property('href', (string) $url);

		$phrase = $this->user['name'] === 'Guest' ? 'Log In' : 'Log Out';
		$a->wrapAround($phrase);
		return $a;
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

				return $this->site->getDesignation();
			case "user":

				if(!isset($this->user))
					return 'Installer';

				return $this->user['name'];
			case "userlink":

				if(!isset($this->user))
					return 'Installer';

				$url = $this->user->getUrl();
				return $url->getLink($this->user['name']);

			case "loginLink":
				
				if(!isset($this->user))
					return '';

				return $this->loginLink();

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
			case "userlink":
			case "loginLink":
				return true;
			default:
				return false;
		}
	}
}

?>
