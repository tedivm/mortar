<?php

class TesseraActionThreadAdd extends ModelActionLocationBasedAdd
{

	protected function getForm()
	{
		$form = parent::getForm();

		$form->changeSection('post')->
			setlegend('Post Body')->
			createInput('post_content')->
			setType('html')->
			addRule('required');

		$inputGroups = $this->getInputGroups($form->getInputList());

		if(isset($inputGroups['location'])) {
			foreach($inputGroups['location'] as $name) {
				$input = $form->getInput('location_' . $name);

				if ($name === 'publishDate')
					$input->setValue(strtotime($input->property('value')));

				$input->setType('hidden');
			}
		}

		return $form;
	}

	protected function processInput($input)
	{
		$threadSaved = parent::processInput($input);

		$inputNames = array_keys($input);
		$inputGroups = $this->getInputGroups($inputNames);

		$user = ActiveUser::getUser();

		if( ($threadSaved === false) || (!isset($inputGroups['post'])) )
			return false;

		$message = new TesseraModelMessage();
		$location = $message->getLocation();

		$message->setParent($this->model->getLocation());
		$message['content'] = $input['post_content'];
		$message['title'] = $this->model['title'];

		$location->setOwner($user);
		$location->setPublishDate($this->model->getLocation()->getPublishDate());

		return $message->save();
	}
}
?>