<?php

class BentoBlogModelBlog extends LocationModel
{
	static public $type = 'Blog';
	public $allowedChildrenTypes = array('BlogEntry');
}


?>