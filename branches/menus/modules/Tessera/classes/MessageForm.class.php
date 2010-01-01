<?php

class TesseraMessageForm extends LithoPageForm {

	protected function define()
	{
		parent::define();

		$this->changeSection('info')->
			setLegend('Info')->
			createInput('model_replyTo')->
			setType('hidden');
	}
}

?>
