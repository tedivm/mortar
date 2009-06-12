<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage ModelSupport
 */

/**
 * This class handles editing resources that are already present in the system. It is largely based on the
 * ModelActionEdit class.
 *
 * @package System
 * @subpackage ModelSupport
 */
class ModelActionEdit extends ModelActionAdd
{
	/**
	 * This function calls the parent::getForm function, but then overwrites the default values with the actual values
	 * the model has set.
	 *
	 * @access protected
	 * @return Form
	 */
	protected function getForm()
	{
		$form = parent::getForm();
		$inputGroups = $this->getInputGroups(($form->getInputList()));

		foreach($inputGroups['model'] as $name)
		{
			$input = $form->getInput('model_' . $name);


			if($input instanceof FormInput)
			{
				$input->setValue($this->model[$name]);
			}else{
				//check boxes
			}
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