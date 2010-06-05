<?php

class LithoPageForm extends LocationModelForm
{
	protected function createCustomInputs()
	{
		$this->changeSection('info')->
			setlegend('Page Information')->
			createInput('model_title')->
				setLabel('Title')->
				setType('title')->
				addRule('required');

			$this->createInput('model_description')->
				setType('textarea')->
				setLabel('Description');;

			$this->createInput('model_keywords')->
				setType('textarea')->
				setLabel('Keywords');

			$this->changeSection('model_content')->
			setlegend('Page Content')->
			createInput('model_content')->
				setType('richtext')->
				addRule('required');
	}

	protected function populateCustomInputs()
	{
		$this->changeSection('model_content')->
			createInput('model_note')->
			setLabel('Note')->
			addRule('maxlength', 200);
	}

}

?>