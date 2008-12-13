<?php
AutoLoader::import('BentoCMS');

class BentoBlogActionListPosts extends BentoCMSActionListPages
{
	static $requiredPermission = 'Read';
	public $AdminSettings = array('linkLabel' => 'List Posts',
									'linkTab' => 'Content',
									'headerTitle' => 'List Posts',
									'linkContainer' => 'CMS');

	protected $resourceType = 'Post';
	protected $resourceHandler = 'BentoBlogPost';
}


?>