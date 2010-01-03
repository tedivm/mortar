<?php

class InstallMenuSystem extends MenuSystem
{

	public function __construct()
	{
		$url = new Url();
		$url->format = 'admin';
		$url->module = 'Installer';

		$installerUrl = clone $url;
		$installerUrl->action = 'Install';
		$link = $requirementUrl->getLink('Install');
		$this->addItemToSubmenu('primary', 'Installation', $link, 'Install');

		$requirementUrl = clone $url;
		$requirementUrl->action = 'Requirements';
		$link = $requirementUrl->getLink('Check Requirements');
		$this->addItemToSubmenu('primary', 'Installation', $link, 'Check Requirements');

		$htaccessUrl = clone $url;
		$htaccessUrl->action = 'htaccess';
		$link = $requirementUrl->getLink('htaccess File');
		$this->addItemToSubmenu('primary', 'Installation', $link, 'htaccess File');
	}
}

