<?php

class GraffitiPluginModelFormTags
{
	public function adjustForm(Model $model, Form $baseForm)
	{
		if(!GraffitiTagger::canTagModelType($model->getType()))
			return null;

		if(!method_exists($model, 'getLocation'))
			return null;

		$loc = $model->getLocation();
		$values = array();

		if($loc->getName() !== 'tmp') {
			$user = $loc->getOwner();
			$values = GraffitiTagLookUp::getUserTags($loc, $user);
		}

		$baseForm->changeSection('tags')->
			setLegend('Tags');

		$input = $baseForm->createInput('tags')->
			setLabel('Tags')->
			setType('tag')->
			property('multiple', 'true');

		$input->property('value', $values);
	}

	public function processAdjustedInputPost(Model $model, $input)
	{
		if(!GraffitiTagger::canTagModelType($model->getType()))
			return null;

		if(!method_exists($model, 'getLocation'))
			return null;

		$loc = $model->getLocation();
		$owner = $loc->getOwner();

		GraffitiTagger::clearTagsFromLocation($loc, $owner); var_dump($input['tags']);
		GraffitiTagger::tagLocation((array) $input['tags'], $loc, $owner);
	}
}

?>