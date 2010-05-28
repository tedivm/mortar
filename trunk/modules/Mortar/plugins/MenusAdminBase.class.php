<?php

class MortarPluginMenusAdminBase
{

	public function addMenuItems($menuSys)
	{
		$currentUrl = Query::getUrl();

		$location = $currentUrl->locationId;
		$action = $currentUrl->action;
		$module = $currentUrl->module;

		$url = new Url();
		$url->format = 'Admin';

		$site = ActiveSite::getSite();
		$loc = $site->getLocation();
		$url->location = $loc;		
		$link = $url->getLink('Home');
		$menuSys->addItemToSubmenu('primary', 'Home', $link, 'Home', 'auto', $url);

		$url = new Url();
		$url->format = 'Admin';
		$url->module = 'Mortar';

		$url->action = 'Dashboard';
		$link = $url->getLink("Dashboard");
		$menuSys->addItemToSubmenu('primary', 'Dashboard', $link, 'Dashboard', 'auto', $url);

		if(isset($action))
			$url->a = $action;
		if(isset($location))
			$url->l = $location;
		if(isset($module))
			$url->m = $module;

		if(!ActiveUser::isLoggedIn())
		{
			$url->action = 'LogIn';
			$link = $url->getLink("Log In");
			$menuSys->addItemToSubmenu('primary', 'Log In', $link, 'Log In', 'auto', $url);
		}else{
			$url->action = 'LogOut';
			$link = $url->getLink("Log Out");
			$menuSys->addItemToSubmenu('primary', 'Log In', $link, 'Log Out', 'auto', $url);
		}

		$menuSys->addItemToSubmenu('primary', 'Settings', '<a href="#">Settings</a>', 'Settings', 'auto');

		$url = clone $url;
		$url->action = 'ClearCache';
		$link = $url->getLink("Clear Cache");
		$menuSys->addItemToSubmenu('primary', 'Settings', $link, 'Clear Cache', 'auto', $url);

		$url = clone $url;
		$url->action = 'MaintenanceMode';
		$link = $url->getLink("Maintenance Mode");
		$menuSys->addItemToSubmenu('primary', 'Settings', $link, 'Maintenance Mode', 'auto', $url);

		$url = clone $url;
		$url->action = 'InstallModule';
		$link = $url->getLink("Install Module");
		$menuSys->addItemToSubmenu('primary', 'Settings', $link, 'Install Modules', 'auto', $url);

		$url = clone $url;
		$url->action = 'MarkupSettings';
		$link = $url->getLink("Markup Settings");
		$menuSys->addItemToSubmenu('primary', 'Settings', $link, 'Markup Settings', 'auto', $url);



		$url = new Url();
		$url->type = 'User';
		$url->action = 'Index';
		$url->format = 'Admin';
		$link = $url->getLink("Users");
		$menuSys->addItemToSubmenu('primary', 'Manage Users', $link, 'Users', 'auto', $url);

		$url = clone $url;
		$url->action = 'Add';
		$link = $url->getLink("Add User");
		$menuSys->addItemToSubmenu('primary', 'Manage Users', $link, 'List Users', 'auto', $url);

		$url = new Url();
		$url->type = 'MemberGroup';
		$url->action = 'Index';
		$url->format = 'Admin';
		$link = $url->getLink("Groups");
		$menuSys->addItemToSubmenu('primary', 'Manage Groups', $link, 'Groups', 'auto', $url);

		$url = clone $url;
		$url->action = 'Add';
		$link = $url->getLink("Add Group");
		$menuSys->addItemToSubmenu('primary', 'Manage Groups', $link, 'Add New Group', 'auto', $url);
	}
}

?>