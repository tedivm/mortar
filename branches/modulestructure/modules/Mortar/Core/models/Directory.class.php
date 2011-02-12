<?php

class MortarCoreModelDirectory extends LocationModel
{
	static public $type = 'Directory';
	protected $table = 'directories';

	public $allowedChildrenTypes = array('Directory');
}

?>