<?php

class ChalkBlogForm extends LocationModelForm {

	protected function createCustomInputs()
	{
		$this->changeSection('info')->
			setlegend('Blog Information');

		$this->createInput('model_subtitle')->
			setLabel('Subtitle');
	}
}

?>