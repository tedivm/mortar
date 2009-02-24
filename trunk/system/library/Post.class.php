<?php
/**
 * BentoBase
 *
 * A framework for developing modular applications.
 *
 * @package		BentoBase
 * @author		Robert Hafner
 * @copyright	Copyright (c) 2007, Robert Hafner
 * @license		http://www.mozilla.org/MPL/
 * @link		http://www.bentobase.org
 */



/**
 * Post Class
 *
 * This class stores all of the variables from $_POST and filters them against XSS
 * This class is a singleton, so it needs to be initialized through GetInstance.
 * It can be accessed as an array, with the array key corrosponding to a config
 * variable.
 *
 * @package		BentoBase
 * @subpackage	Main_Classes
 * @category	Configuration
 * @author		Robert Hafner
 */
class Post extends ArrayObject
{
	static $instance;

	public function __construct($baseArray = null)
	{
		if(isset(self::$instance))
			throw new BentoError('Constructor for singleton ' . get_class($this) . ' called twice');

		$this->load($baseArray);
		$this->filter();
	}

	protected function load($baseArray = null)
	{
		if(is_null($baseArray))
			$baseArray = $_POST;

		$baseArray = ((function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc())
								|| (ini_get('magic_quotes_sybase')
										&& (strtolower(ini_get('magic_quotes_sybase'))!="off")))

			 ? stripslashes_deep($baseArray)
			 : $baseArray;

		foreach ($baseArray as $key => $value)
		{
			$this[$key] = $value;
		}
	}

	protected function filter()
	{

	}

	/**
	 * Returns the stored instance of the Post object. If no object is stored, it will create it
	 *
	 * @return Post
	 */
	public static function getInstance()
	{
		if(!isset(self::$instance)){
			$object = __CLASS__;
			self::$instance = new $object();
		}
		return self::$instance;
	}

	public function getRaw($key)
	{
		return $this->variables[$key];
	}

	public function get_raw($key)
	{
		return $this->variables[$key];
	}

	public function withHtml($key)
	{
		if(is_array($this->variables[$key]))
		{
			$XSS = new XSS();
			$temp =  $this->variables[$key];
			@array_walk_recursive($temp, array($XSS, 'filter'));	// rph look at this
			return $temp;
		}else{
			$XSS = new XSS();
			return $XSS->filter($this->variables[$key]);
		}
	}

	public function alphaNumeric($key)
	{
		if(is_array($this->variables[$key]))
		{
			@array_walk_recursive($temp, array(isAlphaNumberic, 'filter'));	// rph look at this
			return $temp;
		}else{
			return $this->isAlphaNumberic($this->variables[$key]);
		}
	}

	public function isAlphaNumberic($string)
	{
		return preg_replace("/[^a-zA-Z0-9s]/", "", $string);
	}






	public function offsetExists($offset)
	{
		if(isset($this->variables[$offset]))
		{
			return true;
		}

		return false;

	}

	public function offsetGet($offset)
	{

		if(is_array($this->get_raw($offset)))
		{
			$temp =  $this->get_raw($offset);
			array_walk_recursive($temp, 'htmlentities');
			return $temp;
		}

		switch ($offset) {
			case 'password':
				return $this->variables['password'];
				break;
			case 'zipcode':
				return sprintf("%05u", $this->variables['zipcode']);
			default:
				if($this->variables[$offset] === true || $this->variables === false)
					return $this->variables[$offset];

				return htmlentities($this->variables[$offset]);
				break;
		}

	}

	public function offsetSet($offset, $value)
	{
		return $this->variables[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->variables[$offset]);
		return true;
	}


}


// A new class for file uploads should end up in here at some point


?>