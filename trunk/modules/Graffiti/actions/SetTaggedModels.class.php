<?php

class GraffitiActionSetTaggedModels extends ActionBase
{
	static $requiredPermission = 'System';

	protected $form;

	protected function logic()
	{
		$form = new Form('SetTaggedModels');
		$this->form = $form;

		$modelList = ModelRegistry::getModelList();

		if(!$modelList || count($modelList) < 1)
			throw new CoreError('Unable to load modules from system.');

		foreach($modelList as $model)
		{
			$input = $form->createInput('model_' . $model);

			$input->setType('checkbox')->
					setLabel($model);

			if(GraffitiTagger::canTagModelType($model))
				$input->check(true);
		}


		if($inputs = $form->checkSubmit())
		{
			foreach($modelList as $model)
			{
				$enableTagging = (isset($input['model_' . $model]) && $input['model_' . $model]);
				GraffitiTagger::toggleTaggingForModel($enableTagging);
			}
		}else{

		}
	}

	public function viewAdmin($page)
	{

	}

}

?>