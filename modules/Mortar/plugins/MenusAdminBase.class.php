<?php

class MortarPluginMenusAdminBase
{

	public function addMenuItems($menuSys)
	{
		$url = new Url();
		$url->format = 'Admin';

		$site = ActiveSite::getSite();
		$loc = $site->getLocation();
		$url->location = $loc;		
		$link = $url->getLink('Home');
		$menuSys->addItemToSubmenu('primary', 'Meta', $link, 'Home', 'auto', $url);

		$url = new Url();
		$url->format = 'Admin';
		$url->module = 'Mortar';

		if(!ActiveUser::isLoggedIn())
		{
			$url->action = 'LogIn';
			$link = $url->getLink("Log In");
			$menuSys->addItemToSubmenu('primary', 'Meta', $link, 'Log In', 'auto', $url);
		}else{
			$url->action = 'LogOut';
			$link = $url->getLink("Log Out");
			$menuSys->addItemToSubmenu('primary', 'Meta', $link, 'Log Out', 'auto', $url);
		}

		$url = clone $url;
		$url->action = 'ClearCache';
		$link = $url->getLink("Clear Cache");
		$menuSys->addItemToSubmenu('primary', 'Environment', $link, 'Clear Cache', 'auto', $url);

		$url = clone $url;
		$url->action = 'MaintenanceMode';
		$link = $url->getLink("Maintenance Mode");
		$menuSys->addItemToSubmenu('primary', 'Environment', $link, 'Maintenance Mode', 'auto', $url);

		$url = clone $url;
		$url->action = 'InstallModule';
		$link = $url->getLink("Install Module");
		$menuSys->addItemToSubmenu('primary', 'Modules', $link, 'Install Modules', 'auto', $url);



		$url = new Url();
		$url->type = 'User';
		$url->action = 'Add';
		$url->format = 'Admin';
		$link = $url->getLink("Add User");
		$menuSys->addItemToSubmenu('primary', 'Manage Users', $link, 'Add User', 'auto', $url);

		$url = clone $url;
		$url->action = 'Index';
		$link = $url->getLink("List Users");
		$menuSys->addItemToSubmenu('primary', 'Manage Users', $link, 'List Users', 'auto', $url);

		$url = new Url();
		$url->type = 'MemberGroup';
		$url->action = 'Add';
		$url->format = 'Admin';
		$link = $url->getLink("Add Group");
		$menuSys->addItemToSubmenu('primary', 'Manage Groups', $link, 'Add Groups', 'auto', $url);

		$url = clone $url;
		$url->action = 'Index';
		$link = $url->getLink("List Groups");
		$menuSys->addItemToSubmenu('primary', 'Manage Groups', $link, 'List Groups', 'auto', $url);
	}
}

?>