<?php

class TesseraThreadForm extends Form
{
	protected function define()
	{
		$this->changeSection('info')->
			setLegend('Thread')->
			createInput('model_title')->
			setLabel('Title')->
			setType('title')->
			addRule('Required');

	}
}

?>
