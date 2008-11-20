<?php

class HtmlHelper
{
	public $page;
	
	public function __construct()
	{
		$this->page = ActivePage::get_instance();
	}
	
	
}


?>