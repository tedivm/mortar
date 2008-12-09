<?php

class BentoCMSActionRemovePage extends Action
{

	static $requiredPermission = 'Delete';
	public $AdminSettings = array('linkTab' => 'Content',
									'headerTitle' => 'Delete Page');

	protected $status;

	public function logic()
	{
		$info = InfoRegistry::getInstance();
		$cmsPage = new BentoCMSCmsPage($info->Runtime['id']);

		if($this->moduleId != $cmsPage->property('module'))
			throw new BentoError('Module/Page mismatch');

		$form = new Form($this->actionName);
		$this->form = $form;

		$form->changeSection('info')->
			setSectionIntro('Are you sure you want to delete this page?')->
			createInput('submit')->
				setType('submit')->
				addRule('required')->
				property('value', 'Delete')->
			getForm();

		if($form->checkSubmit())
		{
			$cmsPage->property('status', 'deleted');
			$cmsPage->save();
			$this->status = 'deleted';
		}
	}

	public function viewAdmin()
	{
		if($this->status == 'deleted')
		{
			return 'Page successfully deleted.';
		}else{
			return $this->form->makeDisplay();
		}
	}
}

?>