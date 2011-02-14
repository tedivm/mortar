<?php

class LithoCorePageForm extends LocationModelForm
{
	protected function createCustomInputs()
	{
		$this->changeSection('info')->
			setlegend('Page Information');

			$this->createInput('model_description')->
				setType('textarea')->
				setLabel('Description');

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
		$input = $this->getInput('location_title');
		if(isset($input) && $input)
			$input->setValue($this->model->title);

		$this->changeSection('model_content')->
			createInput('model_note')->
			setLabel('Note')->
			addRule('maxlength', 200);
	}
}

?>