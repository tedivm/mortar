<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage UserInputs
 */

/**
 * This class is used to retrieve Get inputs.
 *
 * @package System
 * @subpackage UserInputs
 */
class Get
{
	/**
	 * This function processes Get inputs and returns those inputs as an array. As part of the processing it strips out
	 * any magic quotes, if they were added.
	 *
	 * @return array
	 */
	static public function getArray()
	{
		$queryArray = get_magic_quotes_gpc() ? array_map(array('Get', 'stripslashes_deep'), $_GET) : $_GET;
		return $queryArray;
	}


	static public function stripslashes_deep($value)
	{
		$value = is_array($value)
			? array_map('stripslashes_deep', $value)
			: stripslashes($value);

		return $value;
	}
}

?>