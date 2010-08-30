<?php

class MarkupSmartyPants implements MarkupPost
{
	protected $smartyPantsAttr = 2;

	public function prettifyText($text)
	{
		$sp = new SmartyPantsTypographer_Parser($this->smartyPantsAttr);
		return $sp->transform($text);
	}
}

?>