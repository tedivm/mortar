<?php

class BentoBaseModelRoot extends LocationModel
{
	static public $type = 'Root';
	public $allowedChildrenTypes = array('Site');
}

?>