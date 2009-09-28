<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package		Library
 * @subpackage	Filesystem
 */

/**
 * This class is used to read from and write data to ini files. The data is accessed through the get/set magic
 * functions.
 *
 * @package		Library
 * @subpackage	Filesystem
 */
class IniFile
{
	/**
	 * This is the path to the file bring worked with.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * This is the array of data being worked with.
	 *
	 * @var array
	 */
	protected $content = array();

	/**
	 * If passed a readable path as an argument, the initial data is loaded from that file.
	 *
	 * @param string $path
	 */
	public function __construct($path)
	{
		$this->path = $path;
		if(is_readable($path))
		{
			$this->read();
		}
	}

	/**
	 * This function reads the data from the ini file. Its a wrapper around parse_ini_file, with sections enabled.
	 *
	 */
	public function read()
	{
		$this->content = parse_ini_file($this->path, true);
	}

	/**
	 * This function saves back the current data to the iniFile, if that data has changed.
	 *
	 * @return bool
	 */
	public function write()
	{
		if(file_exists($this->path))
		{
			$oldContents = parse_ini_file($this->path, true);
			if($oldContents == $this->content)
				return true;
		}

		$string = '; File generated by Mortar IniFile' . PHP_EOL;
		$string .= '; Last saved ' . date("F j, Y \a\\t g:i a") . PHP_EOL . PHP_EOL;

		foreach($this->content as $section => $settings)
		{
			$string .= '[' . $section . ']' . PHP_EOL . PHP_EOL;
			foreach($settings as $name => $value)
			{
				$string .= $name . ' = ' . $this->writeValue($value) . PHP_EOL;
			}
			$string .= PHP_EOL . PHP_EOL;
		}

		return file_put_contents($this->path, $string);
	}

	/**
	 * This function takes a value and returns a string to represent that value.
	 *
	 * @param mixed $value
	 * @return string
	 */
	protected function writeValue($value)
	{
		if($value === true)
		{
			return 'true';

		}elseif($value === false){

			return 'false';

		}elseif(is_numeric($value)){

			return $value;

		}elseif(is_scalar($value)){

			return '"' . str_replace("\n", "\\\n", $value) . '"';
		}
	}

	/**
	 * Returns an array of saved values.
	 *
	 * @param null|string $section If set, only the values for that section are returned.
	 * @return array
	 */
	public function getArray($section = null)
	{
		return isset($section)
						? (isset($this->content[$section]) ? $this->content[$section] : false)
						: $this->content;
	}

	/**
	 * Returns an item from the Ini file. Can returns either a full section or a specific item.
	 *
	 * @param string $section
	 * @param null|string $name
	 * @return mixed
	 */
	public function get($section, $name = null)
	{
		if(isset($name))
		{
			if(isset($this->content[$section][$name]))
				return $this->content[$section][$name];
		}else{
			if(isset($this->content[$section]))
				return $this->content[$section];
		}
		return null;
	}

	/**
	 * Sets a value for a specific section and item in the form.
	 *
	 * @param string $section
	 * @param string $name
	 * @param bool|int|string $value
	 */
	public function set($section, $name, $value)
	{
		$this->content[$section][$name] = $value;
	}

	/**
	 * This function clears out any set values.
	 *
	 */
	public function clear()
	{
		$this->content = array();
	}

	/**
	 * Checks for the existence of sections or specific inputs.
	 *
	 * @param string $section
	 * @param string|null $name
	 * @return bool
	 */
	public function exists($section, $name = null)
	{
		if(!isset($this->content[$section]))
			return false;

		if(isset($name))
			return isset($this->content[$section][$name]);

		return true;
	}
}


?>