<?php

class TesseraCoreThreadForm extends LocationModelForm
{
	protected function createCustomInputs()
	{
		$this->changeSection('info')->
			setLegend('Thread');

		$this->changeSection('post')->
			setlegend('Post Body')->
			createInput('post_content')->
			setType('richtext')->
			addRule('required');
	}

	protected function populateCustomInputs()
	{
		$this->removeInput('post_content', 'post');
	}

	protected function postProcessCustomInputs($input)
	{
		$inputNames = array_keys($input);
		$inputGroups = $this->getInputGroups($inputNames);

		if(!isset($inputGroups['post']))
			return true;

		$user = ActiveUser::getUser();

		$message = ModelRegistry::loadModel('Message');
		$location = $message->getLocation();

		$message->setParent($this->model->getLocation());
		$message['content'] = $input['post_content'];
		$message['title'] = $this->model['title'];

		$location->setOwner($user);

		return $message->save();
	}
}

?>
