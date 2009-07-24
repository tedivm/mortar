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
 * This class is used to retrieve command line inputs.
 *
 * @package System
 * @subpackage UserInputs
 */
class Argv
{
	/**
	 * This is an array used to take one letter flags and expand them into the appropriate variable name.
	 *
	 * @var array
	 */
	static public $flags = array(	'p' => 'path',
									'f' => 'format',
									'm' => 'module',
									'l' => 'locationId',
									'a' => 'action',
									'u' => 'username',
									'p' => 'password');

	/**
	 * This function processes command line inputs and returns those inputs as an array. As part of the processing it
	 * replaces command line flags with the full input name
	 *
	 * @return array
	 */
	static public function getArray()
	{

		$queryArray = array();
		if($_SERVER['argc'] > 1)
		{
//			RequestWrapper::$ioHandlerType = 'Cli';

			$unprocessedInput = $_SERVER['argv'];
			array_shift($unprocessedInput);

			// We use this instead of foreach() so that we can unset parts of the array and prevent
			// them from being processed.
			while (list($index, $value) = each($unprocessedInput))
			{
				if(strpos($value, '--') === 0)
				{
					$value = substr($value, 2);
					$seperatorPos = strpos($value, '=');
					if(is_numeric($seperatorPos))
					{
						$queryArray[substr($value, 0, $seperatorPos)] = substr($value, $seperatorPos + 1);
					}else{
						$queryArray[$value] = true;
					}

				}elseif(strpos($value, '-') === 0){

					// get each flag as its own charactor
					$arg = str_split($value);

					// chop off that -
					array_shift($arg);

					while (list($flagIndex, $flagChar) = each($arg))
					{
						$flagName = (isset(self::$flags[$flagChar])) ? self::$flags[$flagChar] : strtolower($flagChar);

						// if this is the last flag in the group, check to see if the next argument is its value
						if(!isset($arg[$flagIndex + 1])
							&& isset($unprocessedInput[$index + 1])
							&& strpos($unprocessedInput[$index + 1], '-') !== 0)
						{
							$queryArray[$flagName] = $unprocessedInput[$index + 1];

							// unset the next argument, which was actually the value of this flag
							// so that it doesn't get processed as its own variable.
							unset($unprocessedInput[$index + 1]);
						}else{
							$queryArray[$flagName] = true;
						}

					}
				}
			}//while (list($index, $value) = each($unprocessedInput))

		}

		return $queryArray;
	}
}

?>