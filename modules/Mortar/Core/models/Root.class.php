<?php

class MortarCoreModelRoot extends LocationModel
{
	static public $type = 'Root';
	public $allowedChildrenTypes = array('Site');
}

?>