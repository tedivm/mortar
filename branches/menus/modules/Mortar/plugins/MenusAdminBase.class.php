<?php

class MortarPluginMenusAdminBase
{

	public function addMenuItems($menuSys)
	{
		$url = new Url();
		$url->module = 'Mortar';
		$url->format = 'Admin';

		if(!ActiveUser::isLoggedIn())
		{
			$url->action = 'LogIn';
			$link = $url->getLink("Log In");
			$menuSys->addItemToSubmenu('primary', 'Meta', $link, 'Log In');
		}else{
			$url->action = 'LogOut';
			$link = $url->getLink("Log Out");
			$menuSys->addItemToSubmenu('primary', 'Meta', $link, 'Log Out');
		}

		$url = clone $url;
		$url->action = 'ClearCache';
		$link = $url->getLink("Clear Cache");
		$menuSys->addItemToSubmenu('primary', 'Environment', $link, 'Clear Cache');

		$url = clone $url;
		$url->action = 'MaintenanceMode';
		$link = $url->getLink("Maintenance Mode");
		$menuSys->addItemToSubmenu('primary', 'Environment', $link, 'Maintenance Mode');

		$url = clone $url;
		$url->action = 'InstallModule';
		$link = $url->getLink("Install Module");
		$menuSys->addItemToSubmenu('primary', 'Modules', $link, 'Install Modules');



		$url = new Url();
		$url->type = 'User';
		$url->action = 'Add';
		$url->format = 'Admin';
		$link = $url->getLink("Add User");
		$menuSys->addItemToSubmenu('primary', 'Manage Users', $link, 'Add User');

		$url = clone $url;
		$url->action = 'Index';
		$link = $url->getLink("List Users");
		$menuSys->addItemToSubmenu('primary', 'Manage Users', $link, 'List Users');

		$url = new Url();
		$url->type = 'MemberGroup';
		$url->action = 'Add';
		$url->format = 'Admin';
		$link = $url->getLink("Add Group");
		$menuSys->addItemToSubmenu('primary', 'Manage Groups', $link, 'Add Groups');

		$url = clone $url;
		$url->action = 'Index';
		$link = $url->getLink("List Groups");
		$menuSys->addItemToSubmenu('primary', 'Manage Groups', $link, 'List Groups');
	}
}

?>