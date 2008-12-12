<?php

class BentoCMSActionEditPage extends Action
{
	static $requiredPermission = 'Edit';

	public $AdminSettings = array('linkLabel' => 'Pages',
									'linkTab' => 'Content',
									'headerTitle' => 'Edit Page',
									'linkContainer' => 'CMS');

	protected $form;
	protected $success;

	public function logic()
	{
		$info = InfoRegistry::getInstance();

		if(is_numeric($info->Runtime['id']))
		{

			$cms = new BentoCMSCmsPage($info->Runtime['id']);


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

		$this->form = new BentoCMSPageForm($this->actionName);

		$this->form->getInput('title')->
			property('value', $current['title']);

		$this->form->getInput('description')->
			property('value', $current['description']);

		$this->form->getInput('content')->
			property('value', $current['content']);

		$this->form->getInput('keywords')->
			property('value', $current['keywords']);

		$this->form->getInput('name')->
			property('value', $current['name']);


		if($this->form->checkSubmit())
		{
			$inputHandler = $this->form->getInputhandler();
			$cms->property('keywords', $inputHandler['keywords']);
			$cms->property('description', $inputHandler['description']);
			$cms->property('name', $inputHandler['name']);

			$cmsContent->property('title', $inputHandler['title']);
			$cmsContent->property('content', $inputHandler['content']);

			if($cms->save() && $cmsContent->save())
			{
				$cmsContent->makeActive();
				$this->success = true;
			}
		}

	}

	public function viewAdmin()
	{
		if($this->form->wasSubmitted())
		{
			if($this->success)
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