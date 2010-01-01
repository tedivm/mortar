<?php

class TesseraActionDiscussionAdd extends ModelActionLocationBasedAdd
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

		return $form;
	}

}

?>