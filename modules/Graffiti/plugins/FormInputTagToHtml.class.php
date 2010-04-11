<?php

class GraffitiPluginFormInputTagToHtml extends MortarPluginFormInputUserToHtml
{
	protected function runCheck(FormInput $input)
	{
		if($input->type != 'tag')
			return false;

		return true;
	}

	protected function getUrl(FormInput $input)
	{
		$url = new Url();
		$url->module = 'Graffiti';
		$url->format = 'json';
		$url->action = 'TagList';

		return $url;
	}

	protected function getString($id, $baseString)
	{
		GraffitiTagLookUp::getTagFromId($id);

		if($tag = GraffitiTagLookUp::getTagFromId($id))
			$baseString .= $tag . ', ';

		return $baseString;
	}
}

?>