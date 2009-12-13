<?php

class GraffitiPluginCustomInstall extends MortarPluginCustomInstall
{

	public function run()
	{
		Hook::registerPlugin('system', 'adminInterface', 'navigation', $this->packageId, 'AdminNav');
		Hook::registerPlugin('Forms', 'HtmlConvert', 'tag', $this->packageId, 'FormInputTagToHtml');
		Hook::registerPlugin('Forms', 'checkSubmit', 'tag', $this->packageId, 'FormInputTagCheckSubmit');
	}
}

?>