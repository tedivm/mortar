<?php

class TesseraPluginModelFormComments
{
	public function adjustForm(Model $model, Form $form)
	{
		if(!TesseraComments::canCommentModelType($model->getType()))
			return null;

		if(!method_exists($model, 'getLocation'))
			return null;

		$form->changeSection('comments')->
			setLegend('Comments');

		$form->createInput('allow_comments')->
			setLabel('Allow Comments?')->
			setType('checkbox')->
			check(true);
	}

	public function processAdjustedInputPost(Model $model, $input)
	{
		if(!GraffitiCategorizer::canCategorizeModelType($model->getType()))
			return null;

		if(!method_exists($model, 'getLocation'))
			return null;

		$loc = $model->getLocation();
		if($discussion = $loc->getChildByName('discussion')) {
			if(isset($input['allow_comments']) && $input['allow_comments']) {
				$discussion->setStatus('Open');
			} else {
				$discussion->setStatus('Closed');
			}
		}

		return $discussion->save();
	}
}

?>