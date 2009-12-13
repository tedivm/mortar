<?php

class TesseraModelThread extends LocationModel
{
	static public $type = 'Thread';
	static public $autoName = true;
	public $allowedChildrenTypes = array('Message');
	protected $table = 'tesseraThreads';
}

?>
