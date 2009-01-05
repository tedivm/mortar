<?php

class BentoCMSPageForm extends Form
{
	protected function define()
	{
		$this->changeSection('info')->
			setlegend('Page Information')->
			createInput('title')->
				setLabel('Title')->
				addRule('required');

			$this->createInput('description')->
				setType('textarea')->
				setLabel('Description');;

			$this->createInput('keywords')->
				setType('textarea')->
				setLabel('Keywords');

			$this->createInput('name')->
				setLabel('Name (as appears in URLS)')->
				addRule('required');

			$this->changeSection('content')->
			setlegend('Page Content')->
			createInput('content')->
				setType('html')->
				addRule('required');

	}
}

?>