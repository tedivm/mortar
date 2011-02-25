<?php

class TesseraCoreModelForum extends LocationModel
{
	static public $type = 'Forum';
	public $allowedChildrenTypes = array('Forum', 'Thread');
	protected $table = 'tesseraForums';
}

?>
