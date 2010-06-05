<?php

class TesseraModelThread extends LocationModel
{
	static public $type = 'Thread';
	public $allowedChildrenTypes = array('Message');
	protected $table = 'tesseraThreads';
	static public $richtext = 'markdown';
}

?>
