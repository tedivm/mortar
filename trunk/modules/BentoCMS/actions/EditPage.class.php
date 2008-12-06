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
			$current['mod_id'] = $cms->property('module');
			$current['pageId'] = $cms->property('id');
			
			$current['title'] = $cmsContent->property('title');
			$current['content'] = $cmsContent->property('rawContent');
			$current['version'] = $cmsContent->property('contentVersion');
			
		}else{
			throw new ResourceNotFoundError('No page id given.');
		}
		
		
		$form = new Form($this->actionName);
		$this->form = $form;
		
		$form->changeSection('info')->
			setlegend('Page Information')->
			createInput('title')->
				setLabel('Title')->
				addRule('required')->
				property('value', $current['title'])->
			getForm()->
		
			createInput('description')->
				setType('textarea')->
				setLabel('Description')->
				addRule('required')->
				property('value', $current['description'])->
			getForm()->
			
			createInput('keywords')->
				setType('textarea')->
				setLabel('Keywords')->
				addRule('required')->
				property('value', $current['keywords'])->
			getForm()->
			
			
			changeSection('content')->
			createInput('content')->
				setType('html')->
				addRule('required')->
				property('value', $current['content']);

				
		if($form->checkSubmit())
		{
			$inputHandler = $form->getInputhandler();
			$cms->property('keywords', $inputHandler['keywords']);
			$cms->property('description', $inputHandler['description']);
			
			$cmsContent->property('title', $inputHandler['title']);
			$cmsContent->property('content', $inputHandler['content']);
			
			if($cms->save() && $cmsContent->save())
			{
				$cmsContent->makeActive();
				$this->success = true;
			}
			Cache::clear('modules', 'cms', $current['pageId']);
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