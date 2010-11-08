<?php

class TesseraModelMessage extends LithoModelPage
{
	static public $type = 'Message';
	static public $autoName = true;
	static public $usePublishDate = false;
	static public $richtext = 'markdown';

	public $allowedChildrenTypes = array();
	protected $table = array('lithoPages', 'tesseraMessages');
}

?>
