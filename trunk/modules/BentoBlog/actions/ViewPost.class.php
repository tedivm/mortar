<?php
AutoLoader::import('BentoCMS');

class BentoBlogActionViewPost extends BentoCMSActionViewPage
{
	static $requiredPermission = 'Read';
	protected $resourceType = 'Post';
	protected $resourceClass = 'BentoBlogPost';


	protected function htmlContentArea()
	{
		$post = new DisplayMaker();
		$post->setDisplayTemplate($this->loadTemplate('ViewPost'));
		$entry = $this->page;


		$user = new User();
		$user->loadUser($entry->property('author'));
		$post->addContent('author', $user->getName());

		$tagArray = $entry->property('tags');
		$post->addContent('tags', implode($entry->property('tags'), ', '));


	//	$post->addContent('author');


		$link = $this->linkToSelf();
		$link->id = $entry->property('name');
		$post->addContent('link', $link);


		$post->addDate('createdOn', strtotime($entry->property('createdDate')));



		$revision = $entry->getRevision();
		$post->addContent('subject', $revision->property('title'));

		$post->addContent('entry', $revision->property('content'));

		return $post->makeDisplay();
	}

}