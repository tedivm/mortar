<?php

class BentoCMSClassPageForm extends Form
{
	protected function define()
	{
		$this->changeSection('info')->
			setlegend('Page Information')->
			createInput('model_title')->
				setLabel('Title')->
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
				setType('html')->
				addRule('required');

	}
}

?>