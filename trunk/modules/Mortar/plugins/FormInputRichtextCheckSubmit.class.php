<?php

class MortarPluginFormInputRichtextCheckSubmit
{
	protected $input;

	protected $inputName = 'richtext';

	public function setInput(FormInput $input)
	{
		if($input->type != $this->inputName)
			return;

		$this->input = $input;
	}

	public function processInput($inputHandler)
	{
		$name = $this->input->getName();
		if(isset($inputHandler[$name])) {
			$markups = Markup::getEngines();
			$markup = 'html';

			foreach($markups as $m) {
				if($this->input->property($m)) {
					$markup = $m;
					break;
				}
			}

			$engine = Markup::getMarkup($markup);

			$namepieces = explode('_', $name);

			$content = array('raw' 		=> $inputHandler[$name],
					 'filtered' 	=> $engine->markupText($inputHandler[$name])
					 );

			$inputHandler[$name] = $content;
		}
	}
}

?>