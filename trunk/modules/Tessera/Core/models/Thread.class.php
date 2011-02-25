<?php

class TesseraCoreModelThread extends LocationModel
{
	public $allowedChildrenTypes = array('Message');
	protected $table = 'tesseraThreads';

	static public $type = 'Thread';
	static public $richtext = 'markdown';
	static public $defaultStatus = 'Open';
	static public $statusTypes = array('Open', 'Closed');
	static public $editStatus = true;
}

?>
