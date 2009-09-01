<?php

class MortarPluginFormInputUserCheckSubmit //implements FormToHtmlHook
{
	protected $input;

	public function setInput(FormInput $input)
	{
		if($input->type != 'user')
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

					if($user = ActiveUser::getIdFromName($input))
						$selectedMemberGroups[] = $user;
				}

				$inputHandler[$name] = $selectedMemberGroups;
			}else{
				$inputHandler[$name] = ActiveUser::getIdFromName($inputHandler[$name]);
			}
		}
	}
}

?>