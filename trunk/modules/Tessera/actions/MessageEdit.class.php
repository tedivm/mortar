<?php

class TesseraActionThreadEdit extends ModelActionLocationBasedEdit
{

	protected function getForm()
	{
		$form = parent::getForm();
		$inputGroups = $this->getInputGroups($form->getInputList());

		if(isset($inputGroups['location'])) {
			foreach($inputGroups['location'] as $name) {
				$input = $form->getInput('location_' . $name);

				if ($name === 'publishDate')
					$input->setValue(strtotime($input->property('value')));

				$input->setType('hidden');
			}
		}

		if(isset($inputGroups['info'])) {
			$input = $form->getInput('model_replyTo');
			$input->setValue($this->model['replyTo']);
		}

		return $form;
	}
}

?>