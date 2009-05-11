<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 */

/**
 * This class figures how the system has been called and pulls in inputs based off of that
 *
 * @package MainClasses
 */
class Input
{
	/**
	 * This method chooses wether to return a PUT or POST array as a FilteredArray, and processes out any magic quotes
	 *
	 * @return array
	 */
	static function getInput()
	{

		if($_SERVER['REQUEST_METHOD'] == 'PUT')
		{

			parse_str(file_get_contents("php://input"), $inputArray);

		}elseif(isset($_POST)){

			$inputArray = ((function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc())
								|| (ini_get('magic_quotes_sybase')
										&& (strtolower(ini_get('magic_quotes_sybase'))!="off")))
					 ? stripslashes_deep($_POST)
					 : $_POST;

		}

		$arrayObject = new FilteredArray($inputArray);
		return $arrayObject;
	}
}

?>