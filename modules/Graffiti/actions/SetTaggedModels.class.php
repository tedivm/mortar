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

	protected function getForm()
	{
		$form = parent::getForm();

		foreach($this->modelList as $model)
		{
			if(!($handler = ModelRegistry::getHandler($model)))
				continue;

			if(in_array($handler['resource'], array('Root', 'TrashCan', 'TrashBag')))
				continue;

			if(!($instance = ModelRegistry::loadModel($handler['resource'])))
				continue;

			if(!(method_exists($instance, 'getLocation')))
				continue;

			$input = $form->createInput('model_' . $model . '_tag')->
				setPretext('<fieldset class="graffiti_model_settings"><legend>'.$model.'</legend>');

			$input->setType('checkbox')->
				setLabel('Tag');

			if(GraffitiTagger::canTagModelType($model))
				$input->check(true);

			$input = $form->createInput('model_' . $model . '_category')->
				setPosttext('</fieldset>');

			$input->setType('checkbox')->
				setLabel('Categorize');

			if(GraffitiCategorizer::canCategorizeModelType($model))
				$input->check(true);
		}

		return $form;
	}

	protected function processInput($input)
	{
		foreach($this->modelList as $model) {
			$enableTagging = (isset($input['model_' . $model . '_tag']) && $input['model_' . $model . '_tag']);
			GraffitiTagger::toggleTaggingForModel($model, $enableTagging);

			$enableCategories = (isset($input['model_' . $model . '_category']) && $input['model_' . $model . '_category']);
			GraffitiCategorizer::toggleCategoriesForModel($model, $enableCategories);
		}
	}

	public function viewAdmin($page)
	{
		return $this->viewAdminForm();
	}

}

?>