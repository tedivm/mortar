<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Dashboard
 */

/**
 * The Index Control enables the output of the Index action to be registered as a control. 
 *
 * @package Mortar
 * @subpackage Dashboard
 */
class MortarControlIndex extends ActionControl
{
	protected $useLocation = true;

	protected $name = "Index";

	protected $classes = array('two_wide');

	protected $customQuery = array('action' => 'Index');

	protected function setName()
	{
		if(isset($this->location)) {
			$loc = Location::getLocation($this->location);
			$model = $loc->getResource();

			if(isset($model['title'])) {
				$name = $model['title'];
			} else {
				$name = str_replace('_', ' ', $loc->getName());
			}

			$this->name .= ' of ' . $name;
		}

		return true;
	}
}

?>