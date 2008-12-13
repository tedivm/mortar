<?php

class BentoCMSActionEditPage extends FormAction
{
	static $requiredPermission = 'Edit';

	public $AdminSettings = array('linkLabel' => 'Pages',
									'linkTab' => 'Content',
									'headerTitle' => 'Edit Page',
									'linkContainer' => 'CMS');

	protected $formName = 'BentoCMSPageForm';
	protected $resourceClass = 'BentoCMSCmsPage';

	protected $resource;

	protected function getForm()
	{
		$form = parent::getForm();
		$info = InfoRegistry::getInstance();

		if(is_numeric($info->Runtime['id']))
		{
			$resourceClass = $this->resourceClass;
			$cms = new $resourceClass($info->Runtime['id']);
			$this->resource = $cms;

			$cmsContent = $cms->getRevision();

			$current['name'] = $cms->property('name');
			$current['keywords'] = $cms->property('keywords');
			$current['description'] = $cms->property('description');
			$current['pageId'] = $cms->property('id');
			$current['title'] = $cmsContent->property('title');
			$current['content'] = $cmsContent->property('rawContent');
			$current['version'] = $cmsContent->property('contentVersion');
		}else{
			throw new ResourceNotFoundError('No page id given.');
		}

		$form->getInput('title')->
			property('value', $current['title']);

		$form->getInput('description')->
			property('value', $current['description']);

		$form->getInput('content')->
			property('value', $current['content']);

		$form->getInput('keywords')->
			property('value', $current['keywords']);

		$form->getInput('name')->
			property('value', $current['name']);

		$this->cms = $cms;
		return $form;
	}

	protected function processInput($inputHandler)
	{
		$cmsContent = $this->cms->getRevision();

		$this->cms->property('keywords', $inputHandler['keywords']);
		$this->cms->property('description', $inputHandler['description']);
		$this->cms->property('name', $inputHandler['name']);

		$cmsContent->property('title', $inputHandler['title']);
		$cmsContent->property('content', $inputHandler['content']);

		if($this->cms->save() && $cmsContent->save())
		{
			$cmsContent->makeActive();
			return true;
		}else{
			return false;
		}
	}

	public function viewAdmin()
	{
		if($this->form->wasSubmitted())
		{
			if($this->formStatus)
			{
				$this->AdminSettings['headerSubTitle'] = 'Page successfully edited';
				return '';
			}else{
				$this->AdminSettings['headerSubTitle'] = 'An error has occured while trying to process this form';
			}
		}else{

		}

		$output .= $this->form->makeDisplay();
		return $output;
	}
}

?>