<?php

class MortarCategoryForm extends Form
{
	protected function define()
	{
		$this->changeSection('Info');
		$this->setLegend('Category Information');

		$this->createInput('model_name')->
			setLabel('Category Name')->
			addRule('required');
	}
}

?>