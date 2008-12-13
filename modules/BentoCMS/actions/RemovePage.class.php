<?php

class BentoCMSActionRemovePage extends Action
{

	static $requiredPermission = 'Delete';
	public $AdminSettings = array('linkTab' => 'Content',
									'headerTitle' => 'Delete Page');

	protected $status;

	protected $resourceType = 'Page';
	protected $resourceClass = 'BentoCMSCmsPage';

	public function logic()
	{
		$info = InfoRegistry::getInstance();
		$location = new Location($info->Runtime['id']);

		if($location->getParent()->getId() != $this->location->getId())
			throw new BentoError('Module/Page mismatch');

		if($location->resource_type() != $this->resourceType)
			throw new BentoError('Module/Page mismatch');

		$form = new Form($this->actionName);
		$this->form = $form;

		$form->changeSection('info')->
			setSectionIntro('Are you sure you want to delete this ' . strtolower($this->resourceType) . '?')->
			createInput('submit')->
				setType('submit')->
				addRule('required')->
				property('value', 'Delete')->
			getForm();

		if($form->checkSubmit())
		{
			$resourceClass = $this->resourceClass;
			$cmsPage = new $resourceClass($info->Runtime['id']);
			$cmsPage->property('status', 'deleted');
			$cmsPage->save();
			$this->status = 'deleted';
		}
	}

	public function viewAdmin()
	{
		if($this->status == 'deleted')
		{
			return  $this->resourceType . ' successfully deleted.';
		}else{
			return $this->form->makeDisplay();
		}
	}
}

?>