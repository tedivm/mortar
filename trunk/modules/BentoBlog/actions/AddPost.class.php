<?php
AutoLoader::import('BentoCMS');

class BentoBlogActionAddPost extends BentoCMSActionAddPage
{

	public $AdminSettings = array('linkLabel' => 'Make Post',
									'linkTab' => 'Content',
									'headerTitle' => 'New Blog Post',
									'linkContainer' => 'CMS');

	protected $formName = 'BentoBlogPostForm';
	protected $resourceClass = 'BentoBlogPost';

	protected function processInput($inputHandler)
	{
		if(parent::processInput($inputHandler))
		{
			$tags = explode(',', $inputHandler['tags']);

			foreach($tags as $index => $tag)
			{
				$tags[$index] = preg_replace("/[^a-zA-Z0-9s]/", "", $tag);
			}


			$user = ActiveUser::getInstance();
			$user = ActiveUser::getInstance();
			$this->resource->property(array('tags' => $tags,
									'author', $user->getId()));

			$this->resource->save();

		}else{

		}
	}

}


?>