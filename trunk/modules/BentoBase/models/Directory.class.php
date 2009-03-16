<?php

class BentoBaseModelDirectory extends AbstractModel
{
	protected $type = 'Directory';
	protected $table = 'Directories';


	protected $id;
	protected $location;
	protected $package;

	protected $attributes;
	protected $properties;
}

?>