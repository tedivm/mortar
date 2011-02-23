<?php

class MortarFormPluginFormInputUserCheckSubmit
{
	protected $input;

	protected $inputName = 'user';

	public function setInput(FormInput $input)
	{
		if($input->type != $this->inputName)
			return;

		$this->input = $input;

	}

	public function processInput($inputHandler)
	{
		$name = $this->input->getName();
		if(isset($inputHandler[$name]))
		{
			if(isset($this->input->properties['multiple']))
			{
				$selectedMemberGroups = array();

				$inputs = explode(',', $inputHandler[$name]);
				foreach($inputs as $input)
				{
					if(!isset($input) || !(strlen($input) > 0))
						continue;

					$input = trim($input);

					if($value = $this->inputToValue($input))
						$selectedMemberGroups[] = $value;
				}

				$inputHandler[$name] = $selectedMemberGroups;
			}else{
				if($value = $this->inputToValue($inputHandler[$name]))
					$inputHandler[$name] = $value;
			}
		}
	}

	protected function inputToValue($input)
	{
		if($user = ActiveUser::getIdFromName($input))
			return $user;
		return false;
	}
}

?>