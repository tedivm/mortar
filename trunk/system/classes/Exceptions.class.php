<?php
/**
 * MortarExceptions
 *
 * While originally developed for Mortar, these exceptions are completely stand alone and are very easy to integrate
 * into existing projects. Developers can either call the different exceptions directly or create inheriting exception
 * classes for the different objects in their projects. Since this library will include the exception's class name when
 * logging or displaying errors the latter method is preferred.
 *
 * MortarException - 0
 * MortarError - E_ERROR
 * MortarWarning - E_WARNING
 * MortarNotice - E_NOTICE
 * MortarDepreciated - E_USER_DEPRECATED
 * MortarUserError - E_USER_ERROR
 * MortarUserWarning - E_USER_WARNING
 * MortarUserNotice - E_USER_NOTICE
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

/*
  Copyright (c) 2009, Robert Hafner
  All rights reserved.

  Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
  following conditions are met:

	* Redistributions of source code must retain the above copyright notice, this list of conditions and the following
		disclaimer.
	* Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the
		following disclaimer in the documentation and/or other materials provided with the distribution.
	* Neither the name of the "Mortar" nor the names of its contributors may be used to endorse or promote
		products derived from this software without specific prior written permission.

  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
  INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
  DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
  SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
  SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
  WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
  OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/



if(!defined('EXCEPTION_OUTPUT'))
{
	/**
	 * Defines the format which should be used for displaying errors- 'text', 'html' and 'simple' are accepted. This
	 * can be defined by developers as long as it's done before this file is loaded.
	 */
	define('EXCEPTION_OUTPUT', (defined('STDIN') ? 'text' : 'html'));
}

/**
 * MortarException
 *
 * This class acts as the base for all the other exception classes. It outputs errors and a stack trace when the
 * debugging level is high enough. This class itself, while having the display and logging functions built in, is
 * designed as a drop in replacement for the Exception class and therefore does not actually log or display anything.
 */
class MortarException extends Exception
{
	/**
	 * Each exception inheriting from this class should define this using one of the php error constants (E_ERROR,
	 * E_WARNING, E_NOTICE, etc) so this class can emulate php actions using the php.ini values.
	 *
	 * @var int
	 */
	protected $errorType = 0;

	/**
	 * Initializes the exception and passes itself off to the static function "handleError", using the errorType
	 * property as the errorLevel for that function. Additionally, if the function "runAction" exists it is called.
	 *
	 * @param string $message
	 * @param int $code This should be left blank or replaced with an error code
	 */
	public function __construct($message = '', $code = 0)
	{
		parent::__construct($message, $code);
		self::handleError($this, $this->errorType);
		if(method_exists($this, 'runAction'))
			$this->runAction();
	}

	/**
	 * Takes in any exception (including those not related to this library) and processes them as an error using the
	 * errorLevel argument. Various error configuration values are extracted from the php runtime, such as the error
	 * reporting and logging values, and if it's appropriate the exception is displayed or sent to the php logger.
	 *
	 * @param Exception $e
	 * @param int $errorLevel This should correspond to the various php error constants (E_ERROR, E_WARNING, etc)
	 */
	static public function handleError(Exception $e, $errorLevel = null)
	{
		if(!isset($errorLevel))
		{
			$errorLevel = 0; // I could just make this the default argument, but I'd prefer knowing whether something
							 // was passed or not.
		}elseif(!is_numeric($errorLevel)){
			$errorLevel = E_ALL | E_STRICT;
		}

		$error_reporting = error_reporting();

		$display_error = ini_get('display_errors');
		$display_error = (strtolower($display_error) == 'on' || $display_error == true);

		$track_error = ini_get('track_errors');
		$track_error = (strtolower($track_error) == 'on' || $track_error == true);

		$log_error = ini_get('log_errors');
		$log_error = (strtolower($log_error) == 'on' || $log_error == true);

		$exception_format = defined('EXCEPTION_OUTPUT') ? strtolower(EXCEPTION_OUTPUT) : 'html';

		if($track_error || $log_error || ($display_error && $exception_format == 'simple'))
			$errorSimpleText = static::getAsSimpleText($e);

		if($track_error == true || strtolower($track_error) == 'on')
		{
			global $php_errormsg;
			$php_errormsg = $errorSimpleText;
		}

		if($error_reporting & $errorLevel) // boolean &, not conditional &&
		{
			if($log_error)
				error_log($errorSimpleText);

			if($display_error)
			{
				switch($exception_format)
				{
					case 'text':
						echo static::getAsText($e) . PHP_EOL . PHP_EOL;
						break;
					case 'simple':
						echo $errorSimpleText . PHP_EOL . PHP_EOL;
						break;

					default:
					case 'html':
						echo static::getAsHtml($e) . PHP_EOL;
						break;
				}
			}
		}
	}

	/**
	 * Returns a single line description of the passed exception, suitable for sending to logging functions.
	 *
	 * @param Exception $e
	 * @return string One line description of the passed exception.
	 */
	static public function getAsSimpleText(Exception $e)
	{
		$file = $e->getFile();
		$line = $e->getLine();
		$message = $e->getMessage();
		$code = $e->getCode();
		$errorClass = get_class($e);
		$program = defined('PROGRAM') ? '*' . PROGRAM . '*  ' : '';
		$output = $program . $errorClass . '(' . $code . '): "' . $message;
		$output .= '" in file: ' . $file . ':' . $line;
		return $output;
	}

	/**
	 * Returns an extended description of the exception, typically used for cli output although it can also be used
	 * inside applications for extended logging.
	 *
	 * @param Exception $e
	 * @return string Extended breakdown of exception, including the stack trace.
	 */
	static public function getAsText(Exception $e)
	{
		// Add header
		$output = PHP_EOL . '*****' . PHP_EOL;;
		$file = $e->getFile();
		$line = $e->getLine();
		$message = $e->getMessage();
		$code = $e->getCode();
		$errorClass = get_class($e);
		$program = defined('PROGRAM') ? PROGRAM . ': ' : '';
		$output .= '*  ' . $program . $errorClass . '(' . $code . '): ' . $message . PHP_EOL;
		$output .= '*    in ' . $file . ' on line ' . $line . PHP_EOL;

		// Add Call Stack
		$stack = array_reverse($e->getTrace());
		if(isset($stack[0]))
		{
			$output .= '*' . PHP_EOL;
			$output .= '*   Call Stack:' . PHP_EOL;
		}

		foreach($stack as $index => $line)
		{
			$function = '';
			if(isset($line['class']))
				$function = $line['class'] . $line['type'];

			$function .= $line['function'];

			if(isset($line['args']) && count($line['args']) > 0)
			{
				$argString = '';
				foreach($line['args'] as $argument)
				{
					$argString .= is_object($argument) ? get_class($argument) : $argument;
					$argString .= ', ';
				}
				$argString = rtrim($argString, ', ');
				$function .= '(' . $argString . ')';
			}else{
				$function .= '()';
			}

			$output .= '*      ' . ($index + 1) . '. ' . $function . PHP_EOL;

			if(isset($line['file']) && isset($line['line']))
				$output .= '*          ' . $line['file'] . ':' . $line['line'] . PHP_EOL;
			$output .= '*' . PHP_EOL;
		}

		return $output . '*****';
	}

	/**
	 * Returns an extended description of the exception formatted in html, typically used to output errors by the
	 * handleError function when display logging is enabled.
	 *
	 * @param Exception $e
	 * @return string Extended breakdown of exception formatted in html.
	 */
	static public function getAsHtml(Exception $e)
	{
		$file = $e->getFile();
		$line = $e->getLine();
		$message = $e->getMessage();
		$code = $e->getCode();

		$errorClass = get_class($e);
		$output = "<font size='1'><table class='Core-error' dir='ltr' border='1' cellspacing='0' cellpadding='2'>
<tr><th align='left' bgcolor='#f57900' colspan='4'>( ! ) " . $errorClass . ": {$message} in <br>{$file} on line <i>{$line}</i></th></tr>
<tr>
<td colspan='3' cellspacing='0' cellpadding='0'>
	<table class='Core-error' dir='ltr' border='1' cellspacing='0' cellpadding='2' width='100%'>
	<th align='left' bgcolor='#e9b96e' colspan='3'>Call Stack</th></tr>
	<tr>
		<th align='center' bgcolor='#eeeeec'>#</th>
		<!--<th align='center' bgcolor='#eeeeec'>Time</th>-->
		<th align='left' bgcolor='#eeeeec'>Function</th>
		<th align='left' bgcolor='#eeeeec'>Location</th>
	</tr>";

		$stack = array_reverse($e->getTrace());

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
				$shortPath = defined('BASE_PATH') ? str_replace(BASE_PATH, '/', $traceLine['file']) : $traceLine['file'];
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
}


/**
 * MortarError
 *
 * This class corresponds to the E_ERROR error level in php, meaning that when E_ERROR is enabled by php (whether by the
 * php.ini file or a runtime configuration change) then it will check to see if error logging or displaying are also
 * activated, and if so will take the appropriate action.
 */
class MortarError extends MortarException { protected $errorType = E_ERROR; }

/**
 * MortarWarning
 *
 * This class corresponds to the E_WARNING error level in php, meaning that when E_WARNING is enabled by php (whether by
 * the php.ini file or a runtime configuration change) then it will check to see if error logging or displaying are also
 * activated, and if so will take the appropriate action.
 */
class MortarWarning extends MortarError { protected $errorType = E_WARNING; }

/**
 * MortarNotice
 *
 * This class corresponds to the E_NOTICE error level in php, meaning that when E_NOTICE is enabled by php (whether by
 * the php.ini file or a runtime configuration change) then it will check to see if error logging or displaying are also
 * activated, and if so will take the appropriate action.
 */
class MortarNotice extends MortarError { protected $errorType = E_NOTICE; }

/**
 * MortarDepreciated
 *
 * This class corresponds to the E_USER_DEPRECATED error level in php, meaning that when E_USER_DEPRECATED is enabled by
 * php (whether by the php.ini file or a runtime configuration change) then it will check to see if error logging or
 * displaying are also activated, and if so will take the appropriate action.
 */
class MortarDepreciated extends MortarError { protected $errorType = E_USER_DEPRECATED; }

/**
 * MortarUserError
 *
 * This class corresponds to the E_USER_ERROR error level in php, meaning that when E_USER_ERROR is enabled by php
 * (whether by the php.ini file or a runtime configuration change) then it will check to see if error logging or
 * displaying are also activated, and if so will take the appropriate action.
 */
class MortarUserError extends MortarError { protected $errorType = E_USER_ERROR; }

/**
 * MortarError
 *
 * This class corresponds to the E_USER_WARNING error level in php, meaning that when E_USER_WARNING is enabled by php
 * (whether by the php.ini file or a runtime configuration change) then it will check to see if error logging or
 * displaying are also activated, and if so will take the appropriate action.
 */
class MortarUserWarning extends MortarError { protected $errorType = E_USER_WARNING; }

/**
 * MortarUserNotice
 *
 * This class corresponds to the E_USER_NOTICE error level in php, meaning that when E_USER_NOTICE is enabled by php
 * (whether by the php.ini file or a runtime configuration change) then it will check to see if error logging or
 * displaying are also activated, and if so will take the appropriate action.
 */
class MortarUserNotice extends MortarError { protected $errorType = E_USER_NOTICE; }
?>