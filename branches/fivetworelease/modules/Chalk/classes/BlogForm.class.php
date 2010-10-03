<?php

class ChalkBlogForm extends LocationModelForm {

	protected function createCustomInputs()
	{
		$this->changeSection('info')->
			setlegend('Blog Information')->
			createInput('model_title')->
			setLabel('Title')->
			setType('title')->
			addRule('required');

		$this->createInput('model_subtitle')->
			setLabel('Subtitle');
	}
}

?>