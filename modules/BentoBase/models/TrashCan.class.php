<?php

class BentoBaseModelTrashCan extends LocationModel
{
	static public $type = 'TrashCan';
	public $allowedChildrenTypes = array('TrashBag');
}

?>