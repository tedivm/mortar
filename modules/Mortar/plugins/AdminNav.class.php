<?php

class MortarPluginAdminNav
{
	protected $staticLinks = array('Universal' => array());
	protected $dynamicLinks = array('Universal' => array());

	public function __construct()
	{
		$url = new Url();
		$url->module = 'Mortar';
		$url->format = 'Admin';

		if(!ActiveUser::isLoggedIn())
		{
			$url->action = 'LogIn';
			$this->addDynamicLink('Log In', $url, 'Main', 'User');
		}else{
			$url->action = 'LogOut';
			$this->addDynamicLink('Log Out', $url, 'Main', 'User');
		}

		$url = clone $url;
		$url->action = 'ClearCache';
		$this->addStaticLink('Clear Cache', $url, 'System', 'Environment');

		$url = clone $url;
		$url->action = 'MaintenanceMode';
		$this->addStaticLink('Maintenance Mode', $url, 'System', 'Environment');

		$url = clone $url;
		$url->action = 'InstallModule';
		$this->addStaticLink('Install Module', $url, 'System', 'Modules');


		$url = new Url();
		$url->type = 'User';
		$url->action = 'Add';
		$url->format = 'Admin';
		$this->addStaticLink('Add User', $url, 'Users', 'Manage Users');

//		$url = new Url();
//		$url->type = 'User';
//		$url->action = 'Add';
//		$url->action = 'EditUser';
//		$this->addStaticLink('Edit User', $url, 'Users', 'Manage Users');

		//$url->action = '';
		//$this->addDynamicLink('name', 'label', $url, 'tab', 'category');


	}

	public function getTabs()
	{
		$keys = array_unique(array_merge(array_keys($this->staticLinks), array_keys($this->dynamicLinks)));
		unset($keys['Universal']);
		return $keys;
	}

	public function getStaticNav($activeTab)
	{
		$tabItems = (isset($this->staticLinks[$activeTab])) ? $this->staticLinks[$activeTab] : array();
		return array_merge_recursive($tabItems, $this->staticLinks['Universal']);
	}

	public function getDynamicNav($activeTab)
	{
		$tabItems = (isset($this->dynamicLinks[$activeTab])) ? $this->dynamicLinks[$activeTab] : array();
		return array_merge_recursive($tabItems, $this->dynamicLinks['Universal']);
	}

	protected function addStaticLink($label, Url $url, $tab, $category = 'StandAlone', $name = null)
	{
		$action = array();

		if(!isset($name))
			$name = htmlentities(str_replace(' ', '_', $label));

		$action['name'] = $name;
		$action['label'] = $label;
		$action['url'] = $url;
		$this->staticLinks[$tab][$category][] = $action;
	}

	protected function addDynamicLink($label, Url $url, $tab, $category = 'StandAlone', $name = null)
	{
		$action = array();

		if(!isset($name))
			$name = htmlentities(str_replace(' ', '_', $label));

		$action['name'] = $name;
		$action['label'] = $label;
		$action['url'] = $url;
		$this->dynamicLinks[$tab][$category][] = $action;
	}

}

?>