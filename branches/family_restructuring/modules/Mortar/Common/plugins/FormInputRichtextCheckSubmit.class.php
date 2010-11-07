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
			$format = $this->input->property('format');
			$markup = isset($format) ? $format : 'html';

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