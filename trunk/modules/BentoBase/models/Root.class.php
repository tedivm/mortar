<?php

class MortarModelRoot extends LocationModel
{
	static public $type = 'Root';
	public $allowedChildrenTypes = array('Site');
}

?>