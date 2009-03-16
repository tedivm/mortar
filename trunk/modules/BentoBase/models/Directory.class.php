<?php

class BentoBaseModelDirectory extends AbstractModel
{
	static public $type = 'Directory';
	protected $table = 'Directories';


	protected $id;
	protected $location;
	protected $package;

	protected $attributes;
	protected $properties;
}

?>