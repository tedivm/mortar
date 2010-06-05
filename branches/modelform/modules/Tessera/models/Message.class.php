<?php

class TesseraModelMessage extends LithoModelPage
{
	static public $type = 'Message';
	static public $autoName = true;
	public $allowedChildrenTypes = array();
	static public $richtext = 'markdown';
	protected $table = array('lithoPages', 'tesseraMessages');
}

?>
