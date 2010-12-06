<?php
/**
 * MortarExceptions
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 */

if(!defined('EXCEPTION_OUTPUT'))
	define('EXCEPTION_OUTPUT', (defined('STDIN') ? 'text' : 'html'));

/**
 * MortarException
 *
 * This class acts as the base for all the other exception classes. It outputs errors and a stack trace when the
 * debugging level is high enough. This class itself, while having the display and logging functions built in, is
 * designed
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
	 * This constructor calls uses the environmental constants ERROR_LOGGING and DEBUG to decide whether or not to log
	 * the error and/or display it, respectively.
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

	static public function getAsSimpleText(Exception $e)
	{
		$file = $e->getFile();
		$line = $e->getLine();
		$message = $e->getMessage();
		$code = $e->getCode();
		$errorClass = get_class($e);
		$output = $errorClass . '(' . $code . '): "' . $message;
		$output .= '" in file: ' . $file . ':' . $line;
		return $output;
	}

	static public function getAsText(Exception $e)
	{
		// Add header
		$output = PHP_EOL . '*****' . PHP_EOL;;
		$file = $e->getFile();
		$line = $e->getLine();
		$message = $e->getMessage();
		$code = $e->getCode();
		$errorClass = get_class($e);
		$output .= '*  ' . $errorClass . '(' . $code . '): ' . $message . PHP_EOL;
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

class MortarError extends MortarException { protected $errorType = E_ERROR; }
class MortarWarning extends MortarError { protected $errorType = E_WARNING; }
class MortarNotice extends MortarError { protected $errorType = E_NOTICE; }
class MortarDepreciated extends MortarError { protected $errorType = E_USER_DEPRECATED; }
class MortarUserError extends MortarError { protected $errorType = E_USER_ERROR; }
class MortarUserWarning extends MortarError { protected $errorType = E_USER_WARNING; }
class MortarUserNotice extends MortarError { protected $errorType = E_USER_NOTICE; }
?>