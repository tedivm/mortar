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
 * This class handles deleting resources from the system.
 *
 * @package System
 * @subpackage ModelSupport
 * @todo write this
 */
class ModelActionLocationBasedDelete extends ModelActionLocationBasedBase
{

	/**
	 * This defines the permission action that the user needs to run this. Permissions are based off of an action and
	 * a resource type, so this value is used with the model type to generate a permissions object
	 *
	 * @access public
	 * @var string
	 */
	public static $requiredPermission = 'Delete';

	/**
	 * Enter description here...
	 *
	 */
	public function start()
	{

	}

	public function viewAdmin()
	{

	}

	public function viewHtml()
	{
		return '';
	}

	public function viewXml()
	{
		return '';
	}

	public function viewJson()
	{
		return '';
	}
}

?>