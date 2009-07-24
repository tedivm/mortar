<?php

class MortarModelTrashCan extends LocationModel
{
	static public $type = 'TrashCan';
	public $allowedChildrenTypes = array('TrashBag');
}

?>