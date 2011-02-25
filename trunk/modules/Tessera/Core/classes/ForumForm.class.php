<?php

class TesseraCoreForumForm extends LocationModelForm
{
	protected function createCustomInputs()
	{
		$this->changeSection('info')->
			setLegend('Forum Information');

		$this->createInput('model_description')->
			setLabel('Description')->
			setType('textarea');
	}
}


?>
