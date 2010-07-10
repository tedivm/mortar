<?php

class TesseraForumForm extends LocationModelForm
{
	protected function createCustomInputs()
	{
		$this->changeSection('info')->
			setLegend('Forum Information')->
			createInput('model_title')->
			setLabel('Title')->
			setType('title')->
			addRule('Required');

		$this->createInput('model_description')->
			setLabel('Description')->
			setType('textarea');
	}
}


?>
