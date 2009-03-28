<?php

class BentoBaseModelDirectory extends AbstractModel
{
	static public $type = 'Directory';
	protected $table = 'directories';
	protected $allowedParents = array('Root', 'Site', 'Directory');



	public function save($parent = null)
	{
		if(!parent::save($parent))
			return false;

		return true;
	}

}

?>