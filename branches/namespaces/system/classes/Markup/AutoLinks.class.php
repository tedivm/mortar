<?php

class MarkupAutoLinks implements MarkupPost
{
	public function prettifyText($text)
	{
		$config = HTMLPurifier_Config::createDefault();
		$config->set('AutoFormat.Linkify', true);

		$pur = new HTMLPurifier($config);
		return $pur->purify($text);

		return $text;
	}
}

?>