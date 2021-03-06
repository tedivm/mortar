<?php

class TesseraCoreActionCommentSettings extends FormAction
{

	public static $settings = array( 'Base' => array('headerTitle' => 'Comment Settings') );

	static $requiredPermission = 'System';
	protected $formName = 'Form';
	protected $modelList;

	public function logic()
	{
		$this->modelList = ModelRegistry::getModelList();

		if(!$this->modelList || count($this->modelList) < 1)
			throw new CoreError('Unable to load modules from system.');

		return parent::logic();
	}

	protected function getForm()
	{
		$info = PackageInfo::loadByName('Tessera', 'Core');
		$id = $info->getId();

		$form = parent::getForm();
		$form->changeSection('models')->
			setLegend('Allow Comments')->
			addSectionClass('mf-toggle-none');

		foreach($this->modelList as $model)
		{
			if(!($handler = ModelRegistry::getHandler($model)))
				continue;

			if($handler['module'] == $id)
				continue;

			if(in_array($handler['resource'], array('Root', 'TrashCan', 'TrashBag')))
				continue;

			if(!($instance = ModelRegistry::loadModel($handler['resource'])))
				continue;

			if(!(method_exists($instance, 'getLocation')))
				continue;

			$input = $form->createInput('model_' . $model . '_comment');

			$input->setType('checkbox')->
				setLabel($model);

			if(TesseraCoreComments::canCommentModelType($model))
				$input->check(true);
		}

		return $form;
	}

	protected function processInput($input)
	{
		foreach($this->modelList as $model) {
			$enableComments = (isset($input['model_' . $model . '_comment']) && $input['model_' . $model . '_comment']);
			TesseraCoreComments::toggleCommentsForModel($model, $enableComments);
		}
	}

	public function viewAdmin($page)
	{
		return $this->viewAdminForm();
	}


}

?>