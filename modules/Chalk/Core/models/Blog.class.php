<?php

class ChalkCoreModelBlog extends LocationModel
{
	static public $type = 'Blog';
	public $allowedChildrenTypes = array('BlogEntry');
	protected $table = 'chalkBlog';
}


?>