<?php

class ChalkBlogForm extends Form {

	protected function define()
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