<?php
AutoLoader::import('BentoCMS');

class BentoBlogActionRemovePost extends BentoCMSActionRemovePage
{
	protected $resourceType = 'Post';
	protected $resourceClass = 'BentoBlogPost';

}

?>