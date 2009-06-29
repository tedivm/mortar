<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage ErrorHandling
 */

/**
 * Base Errer Handler
 *
 * This class acts as the base for all the other exception classes. It outputs errors and a stack trace when the
 * debugging level is high enough. This class itself is reserved for errors
 *
 * @package System
 * @subpackage ErrorHandling
 */
class BentoError extends Exception
{
	/**
	 * This is the minimal debug level that this exception class will output with. For this class it is 1.
	 *
	 * @access protected
	 * @var int
	 */
	protected $debugLevel = 1;

	/**
	 * This constructor calls uses the environmental constants ERROR_LOGGING and DEBUG to decide whether or not to log
	 * the error and/or display it, respectively.
	 *
	 * @access public
	 * @param string $message
	 * @param int $code This should be left blank or replaced with http error codes
	 */
	public function __construct($message = '', $code = 0)
	{
		parent::__construct($message, $code);

		if(method_exists($this, 'runAction'))
			$this->runAction();

		if(!INSTALLMODE && ERROR_LOGGING >= $this->debugLevel)
			RequestLog::logError($this, $this->debugLevel);

		if(DEBUG >= $this->debugLevel)
			$this->debugAction();
	}

	/**
	 * This magic method turns the current exception into a string, allowing it to be outputted to a browser
	 *
	 * @access public
	 * @return string
	 */
	public function __toString()
	{
		$runtimeConfig = Query::getQuery();

		$file = $this->getFile();
		$line = $this->getLine();
		$message = $this->getMessage();
		$code = $this->getCode();


		$actionOutput = (isset($runtimeConfig['action'])) ? $runtimeConfig['action'] : '<i>unset</i>';
		$packageOutput = (isset($runtimeConfig['package'])) ? $runtimeConfig['package'] : '<i>unset</i>';

		$idOutput = (is_numeric($runtimeConfig['id'])) ? $runtimeConfig['id'] : '<i>unset</i>';
		$engineOutput = (isset($runtimeConfig['format'])) ? $runtimeConfig['format'] : '<i>unset</i>';
		$siteOutput = '<i>unset</i>';

	//	if($site = ActiveSite::getSite())
	//		$siteOutput = (is_numeric($site->siteId)) ? $site->siteId : '<i>unset</i>';

		$dispatcher = DISPATCHER;

		$errorClass = get_class($this);
		$output = "<font size='1'><table class='Bento-error' dir='ltr' border='1' cellspacing='0' cellpadding='2'>
<tr><th align='left' bgcolor='#f57900' colspan='4'>( ! ) " . $errorClass . ": {$message} in <br>{$file} on line <i>{$line}</i></th></tr>
<tr><th align='left' bgcolor='#e9b96e' colspan='3'>System Information</th></tr>
<tr>
	<td bgcolor='#eeeeec'><b>Site ID:</b> {$siteOutput}</td>
	<td bgcolor='#eeeeec'><b>Action:</b> {$actionOutput}</td>
	<td bgcolor='#eeeeec'><b>Module:</b> {$packageOutput} </td>
</tr>
<tr>
	<td bgcolor='#eeeeec'><b>ID:</b> {$idOutput}</td>
	<td bgcolor='#eeeeec'><b>Engine:</b> {$engineOutput}</td>
	<td bgcolor='#eeeeec'><b>Dispatcher:</b> {$dispatcher}</td>
</tr>

<tr>
<td colspan='3' cellspacing='0' cellpadding='0'>
	<table class='Bento-error' dir='ltr' border='1' cellspacing='0' cellpadding='2' width='100%'>
	<th align='left' bgcolor='#e9b96e' colspan='3'>Call Stack</th></tr>
	<tr>
		<th align='center' bgcolor='#eeeeec'>#</th>
		<!--<th align='center' bgcolor='#eeeeec'>Time</th>-->
		<th align='left' bgcolor='#eeeeec'>Function</th>
		<th align='left' bgcolor='#eeeeec'>Location</th>
	</tr>";


		$stack = array_reverse($this->getTrace());

		$x = 0;
		foreach($stack as $traceLine)
		{
			$x++;

			if(is_array($traceLine['args']))
			{
				$argValueShort = '';
				$argString = '';
				$argStringLong = '';
				$comma = ' ';
				foreach($traceLine['args'] as $argName => $argValue)
				{
					if(is_string($argValue))
					{

					}elseif(is_object($argValue)){
						$argValue = get_class($argValue);
					}

					if(is_array($argValue))
						$argValue = var_export($argValue, true);

					$argString .= $comma .( (strlen($argValue) > '8') ? substr($argValue, '0', 6) . '..' : $argValue);
					$argStringLong .= $comma . $argValue;
					$comma = ', ';
				}
				$argString = rtrim($argString, ',');
				$argStringLong = rtrim($argStringLong, ',');

			}else{
				$argString = ' ';
			}


			if((isset($traceLine['file'])))
			{
				$shortPath = str_replace(BASE_PATH, '/', $traceLine['file']);
			}else{
				$shortPath = 'Global';
				$traceLine['file'] = '';
				$traceLine['line'] = '';
			}

			$functionName = '';

			if(isset($traceLine['class']))
				$functionName .= $traceLine['class'];


			if(isset($traceLine['type']))
				$functionName .= $traceLine['type'];

			$functionName .= $traceLine['function'];

			$output .= "<tr>
	<td bgcolor='#eeeeec' align='center'>$x</td>
	<!--<td bgcolor='#eeeeec' align='center'>Time</td>-->
	<td title='{$functionName}({$argStringLong})' bgcolor='#eeeeec'>{$functionName}({$argString})</td>
	<td title='{$traceLine['file']}' bgcolor='#eeeeec'>{$shortPath}<b>:</b>{$traceLine['line']}</td>
</tr>";


		}

		$output .= '</table></font>
		</td></tr></table>
		<br>';

		return $output;
	}

	/**
	 * When the DEBUG constant is set to an integer equal to or great than this classes debugLevel this function is run.
	 * Currently it outputs a string version of the exception.
	 *
	 * @access public
	 */
	public function debugAction()
	{
		echo $this;
	}

}

/**
 * BentoWarning exception handler
 *
 * This is an exception handler that deals with Warning-level errors
 *
 * @package System
 * @subpackage ErrorHandling
 */
class BentoWarning extends BentoError
{
	/**
	 * This is the minimal debug level that this exception class will output with. For this class it is 2.
	 *
	 * @access protected
	 * @var int
	 */
	protected $debugLevel = 2;
}

/**
 * BentoNotice exception handler
 *
 * This is an exception handler that deals with Notice-level errors
 *
 * @package System
 * @subpackage ErrorHandling
 */
class BentoNotice extends BentoError
{
	/**
	 * This is the minimal debug level that this exception class will output with. For this class it is 3.
	 *
	 * @access protected
	 * @var int
	 */
	protected $debugLevel = 3;
}

/**
 * BentoUserError exception handler
 *
 * This is an exception handler that deals with User errors
 *
 * @package System
 * @subpackage ErrorHandling
 */
class BentoUserError extends BentoError
{
	/**
	 * This is the minimal debug level that this exception class will output with.
	 *
	 * @access protected
	 * @var int
	 */
	protected $debugLevel = 4;
}

/**
 * BentoInfo exception handler
 *
 * This is an exception handler that deals with Info-level errors
 *
 * @package System
 * @subpackage ErrorHandling
 */
class BentoInfo extends BentoError
{
	/**
	 * This is the minimal debug level that this exception class will output with.
	 *
	 * @access protected
	 * @var int
	 */
	protected $debugLevel = 5;
}

/**
 * Depreciation exception handler
 *
 * This exception is thrown to notify developers they are using depreciated, but available, functions
 *
 * @package System
 * @subpackage ErrorHandling
 */
class BentoDepreciated extends BentoError
{
	/**
	 * This needs to be set to a high value to let the DEPRECIATION_WARNINGS constant control its display.
	 *
	 * @access protected
	 * @var int
	 */
	protected $debugLevel = 9;

	public function __construct($message = '', $code = 0)
	{
		if(DEPRECIATION_WARNINGS == true)
			$this->debugLevel = 1;

		parent::__construct($message, $code);
	}

}

/**
 * Depreciation exception handler
 *
 * This exception is thrown to notify developers they are using depreciated functions that are no longer usable
 *
 * @package System
 * @subpackage ErrorHandling
 */
class BentoDepreciatedError extends BentoDepreciated
{
	/**
	 * This is the minimal debug level that this exception class will output with. For this class it is 1.
	 *
	 * @access protected
	 * @var int
	 */
	protected $debugLevel = 1;
}

/**
 * TypeMismatch exception handler
 *
 * This is thrown when arguments of the wrong type are passed to a method or function
 *
 * @package System
 * @subpackage ErrorHandling
 */
class TypeMismatch extends BentoError
{
	/**
	 * Exception-specific constructor to allow additional information to be passed to the thrown exception.
	 *
	 * @param array $message [0] should be the expected type, [1] should be the received object, and [3] is an optional message
	 * @param int $code
	 */
	public function __construct($message = '', $code = 0)
	{
		if(is_array($message))
		{
			$expectedType = isset($message[0]) ? $message[0] : '';
			$customMessage = isset($message[2]) ? $message[2] : '';

			if(isset($message[1]))
			{
				$receivedObject = $message[1];
				$receivedType = is_object($receivedObject)
									? 'Class ' . get_class($receivedObject)
									: gettype($receivedObject);
			}else{
				$receivedType = 'Null or Unknown';

			}

			$message = 'Expected object of type: ' . $expectedType . ' but received ' . $receivedType . ' ';

			if(isset($customMessage))
				$message .= ' ' . $customMessage;
		}

		parent::__construct($message, $code);
	}
}

/**
 * AuthenticationError exception handler
 *
 * This exception is thrown when someone tries to access an area they do not have permission to access
 *
 * @package System
 * @subpackage ErrorHandling
 */
class AuthenticationError extends BentoUserError {}

/**
 * ResourceNotFoundError exception handler
 *
 * This is thrown when a resource is unable to be found
 *
 * @package System
 * @subpackage ErrorHandling
 */
class ResourceNotFoundError extends BentoUserError {}





?>