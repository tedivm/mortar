<?php
/**
 * Bento Base
 *
 * A framework for developing modular applications.
 *
 * @package		Bento Base
 * @author		Robert Hafner
 * @copyright	Copyright (c) 2007, Robert Hafner
 * @license		http://www.mozilla.org/MPL/
 * @link		http://www.bentobase.org
 */



/**
 * Main Module Error Class
 *
 * responds to errors in the main modules.
 *
 * @package		Bento Base
 * @subpackage	Main_Classes
 * @category	Exception
 * @author		Robert Hafner
 */
class BentoError extends Exception
{
	protected $debugLevel = 1;

	public function __construct($message = '', $code = 0)
	{
		parent::__construct($message, $code);


		if(method_exists($this, 'runAction'))
			$this->runAction();

		if(DEBUG >= $this->debugLevel)
			$this->debugAction();

	}

	public function __toString()
	{
		$config = Config::getInstance();

		$output .= 'Action: ' . $config['action'] . '<br />';
		$output .= 'Module: ' . $config['module'] . '<br />';
		$output .= 'ID: ' . $config['id'] . '<br />';
		$output .= 'Engine: ' . $config['engine'] . '<br />';


		$file = $this->getFile();
		$line = $this->getLine();
		$message = $this->getMessage();
		$code = $this->getCode();

		$site = ActiveSite::getInstance();
		$actionOutput = (isset($config['action'])) ? $config['action'] : '<i>unset</i>';
		$moduleOutput = (isset($config['module'])) ? $config['module'] : '<i>unset</i>';
		$idOutput = (is_numeric($config['id'])) ? $config['id'] : '<i>unset</i>';
		$engineOutput = (isset($config['engine'])) ? $config['engine'] : '<i>unset</i>';
		$siteOutput = (is_numeric($site->siteId)) ? $site->siteId : '<i>unset</i>';
		$dispatcher = DISPATCHER;

		$errorClass = get_class($this);
		$output = "<font size='1'><table class='Bento-error' dir='ltr' border='1' cellspacing='0' cellpadding='2'>
<tr><th align='left' bgcolor='#f57900' colspan='4'>( ! ) " . $errorClass . ": {$message} in <br>{$file} on line <i>{$line}</i></th></tr>
<tr><th align='left' bgcolor='#e9b96e' colspan='3'>System Information</th></tr>
<tr>
	<td bgcolor='#eeeeec'><b>Site ID:</b> {$siteOutput}</td>
	<td bgcolor='#eeeeec'><b>Action:</b> {$actionOutput}</td>
	<td bgcolor='#eeeeec'><b>Module:</b> {$moduleOutput} </td>
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
					$argString .= $comma .( (strlen($argValue) > '8') ? substr($argValue, '0', 6) . '..' : $argValue);
					$argStringLong .= $comma .  $argValue;
					$comma = ', ';
				}
				$argString = rtrim($argString, ',');
				$argStringLong = rtrim($argStringLong, ',');

			}else{
				$argString = ' ';
			}

			$shortPath = str_replace(BASE_PATH, '/', $traceLine['file']);

			$functionName = $traceLine['class'] . $traceLine['type'] . $traceLine['function'];

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

	public function debugAction()
	{
		echo $this;
	}

}

class BentoWarning extends BentoError
{
	protected $debugLevel = 2;
}

class BentoNotice extends BentoError
{
	protected $debugLevel = 3;
}




class AuthenticationError extends BentoError
{
	protected $debugLevel = 2;
}

class ResourceNotFoundError extends BentoError
{
	protected $debugLevel = 2;
}






?>