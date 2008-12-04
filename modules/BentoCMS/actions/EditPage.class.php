<?php

class BentoCMSActionEditPage extends PackageAction
{
	static $requiredPermission = 'Edit';
	
	public $AdminSettings = array('linkLabel' => 'Pages',
									'linkTab' => 'Content',
									'headerTitle' => 'Edit Page',
									'linkContainer' => 'CMS');

	protected $form;
										
	public function logic()
	{
		$info = InfoRegistry::getInstance();
		
		if(is_numeric($info->Runtime['id']))
		{
			$cms = new CMSPage();
			$cms->load_by_pageid($info->Runtime['id']);
			
			$current['name'] = $cms->name;
			$current['title'] = $cms->title;
			$current['content'] = $cms->content;
			$current['keywords'] = $cms->keywords;
			$current['description'] = $cms->description;
			$current['mod_id'] = $cms->mod_id;
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
				property('value', $current['title'])->
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
			$cms = new CMSPage();
			$cms;
		}
			
	}
	
	public function viewAdmin()
	{
		return $this->form->makeDisplay();
	}
}

?>