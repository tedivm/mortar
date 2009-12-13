<?php

class TesseraForumForm extends Form
{
	protected function define()
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
