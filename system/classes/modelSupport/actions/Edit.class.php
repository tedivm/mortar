<?php

class ModelActionEdit extends ModelActionAdd
{

	protected function getForm()
	{
		$form = parent::getForm();



		$inputGroups = $this->getInputGroups(($form->getInputList()));



//		$form = new Form();

		foreach($inputGroups['model'] as $name)
		{
			$input = $form->getInput('model_' . $name);
			$input->setValue($this->model[$name]);
//			$this->model[$name] = $input['model_' . $name];
		}

		if(isset($inputGroups['location']))
		{
			if(in_array('name', $inputGroups['location']))
			{
				$input = $form->getInput('location_name');

				$input->setValue($this->model->getLocation()->getName());
			}
		}




		return $form;
	}
}

?>