<?php

class MarkupHtml implements MarkupEngine
{
	protected $smartyPantsAttr = 2;
	protected $htmlPurifierConfig = null;

	public function markupText($text)
	{
		return $text;
	}

	public function filterText($text)
	{
		$pur = new HTMLPurifier($this->htmlPurifierConfig);
		return $pur->purify($text);
	}

	public function prettifyText($text)
	{
		$sp = new SmartyPantsTypographer_Parser($this->smartyPantsAttr);
		return $sp->transform($text);
	}
}
?>