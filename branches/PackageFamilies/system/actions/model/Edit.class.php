<?php
/**
 * Mortar
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

        public static $settings = array( 'Base' => array( 'headerTitle' => 'Edit', 'useRider' => false ) );

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
		$form->populateInputs();
		return $form;
	}
}

?>