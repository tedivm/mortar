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

	protected function loginLink($returnUrl = false)
	{
		$currentUrl = Query::getUrl();
		$query = Query::getQuery();

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

		$url->format = $query['format'];

		$a = new HtmlObject('a');
		$a->addClass('login_link')->
			property('href', (string) $url);

		$phrase = $this->user['name'] === 'Guest' ? 'Log In' : 'Log Out';
		$a->wrapAround($phrase);

		if($returnUrl) {
			return $url;
		} else {
			return $a;
		}
	}

	protected function loginBox()
	{
		$div = new HtmlObject('div');
		$div->addClass('login-box');
	
		if($this->user['name'] === 'Guest') {
			$url = $this->loginLink(true);

			$form = new MortarLogInForm('logIn');
			$form->setAction($url);

			$div->wrapAround($form->getFormAs());
		} else {
			$p = new HtmlObject('p');
			$p->wrapAround('Logged in as ' . $this->user['name']);
			$link = $this->loginLink();
			$div->wrapAround($p);
			$div->wrapAround($link);
		}

		return (string) $div;
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

			case 'loginBox':

				if(!isset($this->user))
					return '';

				return $this->loginBox();

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
			case "loginBox":
				return true;
			default:
				return false;
		}
	}
}

?>
