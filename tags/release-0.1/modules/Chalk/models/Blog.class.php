<?php

class ChalkModelBlog extends LocationModel
{
	static public $type = 'Blog';
	public $allowedChildrenTypes = array('BlogEntry');
}


?>