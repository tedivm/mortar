<?php
AutoLoader::import('BentoCMS');

class BentoBlogActionEditPost extends BentoCMSActionEditPage
{
	protected $formName = 'BentoBlogPostForm';
	protected $resourceClass = 'BentoBlogPost';

	protected function getForm()
	{
		$form = parent::getForm();
		$tags = $this->resource->property('tags');

		if(count($tags) > 0)
		{
			$form->getInput('tags')->
				property('value', implode(', ', $tags));
		}
		return $form;
	}

	protected function processInput($inputHandler)
	{
		if(parent::processInput($inputHandler))
		{
			$this->resource->property('tags', explode(',', $inputHandler['tags']));
			if($this->resource->save())
				return true;
		}
		return false;
	}
}

?>