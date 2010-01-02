<?php

class MortarPluginMenusAdminBase
{

	public function getMenuItems()
	{
		$links = array();

		$url = new Url();
		$url->module = 'Mortar';
		$url->format = 'Admin';

		$menu = new Menu('primary');

		if(!ActiveUser::isLoggedIn())
		{
			$url->action = 'LogIn';
			$link = $url->getLink("Log In");
			$menu->addItemToSubmenu('Meta', $link, 'Log In');
		}else{
			$url->action = 'LogOut';
			$link = $url->getLink("Log Out");
			$menu->addItemToSubmenu('Meta', $link, 'Log Out');
		}

		$url = clone $url;
		$url->action = 'ClearCache';
		$link = $url->getLink("Clear Cache");
		$menu->addItemToSubmenu('Environment', $link, 'Clear Cache');

		$url = clone $url;
		$url->action = 'MaintenanceMode';
		$link = $url->getLink("Maintenance Mode");
		$menu->addItemToSubmenu('Environment', $link, 'Maintenance Mode');

		$url = clone $url;
		$url->action = 'InstallModule';
		$link = $url->getLink("Install Module");
		$menu->addItemToSubmenu('Modules', $link, 'Install Modules');



		$url = new Url();
		$url->type = 'User';
		$url->action = 'Add';
		$url->format = 'Admin';
		$link = $url->getLink("Add User");
		$menu->addItemToSubmenu('Manage Users', $link, 'Add User');

		$url = clone $url;
		$url->action = 'Index';
		$link = $url->getLink("List Users");
		$menu->addItemToSubmenu('Manage Users', $link, 'List Users');

		$url = new Url();
		$url->type = 'MemberGroup';
		$url->action = 'Add';
		$url->format = 'Admin';
		$link = $url->getLink("Add Group");
		$menu->addItemToSubmenu('Manage Groups', $link, 'Add Groups');

		$url = clone $url;
		$url->action = 'Index';
		$link = $url->getLink("List Groups");
		$menu->addItemToSubmenu('Manage Groups', $link, 'List Groups');

		return $menu->getItems();
	
	}
}

?>