<?php

class MortarModelDirectory extends LocationModel
{
	static public $type = 'Directory';
	static public $usePublishDate = true;
	protected $table = 'directories';

	public $allowedChildrenTypes = array('Directory');
}

?>