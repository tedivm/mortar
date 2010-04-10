<?php

class GraffitiActionSetTaggedModels extends FormAction
{
	public $adminSettings = array('headerTitle' => 'Set Tagged Models');

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

	protected function processInput($input)
	{
		foreach($this->modelList as $model) {
			$enableTagging = (isset($input['model_' . $model]) && $input['model_' . $model]);
			GraffitiTagger::toggleTaggingForModel($enableTagging);
		}
	}

	protected function getForm()
	{
		$form = parent::getForm();

		foreach($this->modelList as $model)
		{
			$input = $form->createInput('model_' . $model);

			$input->setType('checkbox')->
				setLabel($model);

//			if(GraffitiTagger::canTagModelType($model))
//				$input->check(true);
		}

		return $form;
	}

	public function viewAdmin($page)
	{
		return $this->viewAdminForm();
	}

}

?>