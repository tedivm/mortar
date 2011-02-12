<?php

class MortarCoreModelTrashBag extends LocationModel
{
	static public $type = 'TrashBag';
	protected $table = 'trash';
	public $allowedChildrenTypes = array();

	static public $autoName = true;

	public function canHaveChildType($resourceType)
	{
		// anything can be trash!
		return true;
	}

}

?>