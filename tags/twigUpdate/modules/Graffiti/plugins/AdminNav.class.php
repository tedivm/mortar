<?php

class GraffitiPluginAdminNav extends MortarPluginAdminNav
{
	public function __construct()
	{
		$url = new Url();
		$url->module = 'Graffiti';
		$url->format = 'Admin';
		$url->action = 'SetTaggedModels';
		$this->addStaticLink('Model Tagging', $url, 'System', 'Environment');
	}
}

?>