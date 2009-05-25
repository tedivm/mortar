<?php

class BentoBaseModelDirectory extends LocationModel
{
	static public $type = 'Directory';
	protected $table = 'directories';
	protected $allowedParents = array('Root', 'Site', 'Directory');


}

?>