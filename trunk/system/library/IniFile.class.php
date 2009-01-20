<?php

class IniFile
{
	protected $path;

	protected $content = array();

	public function __construct($path)
	{
		$this->path = $path;
		if(is_readable($path))
		{
			$this->read();
		}
	}

	public function read()
	{
		$this->content = parse_ini_file($this->path, true);
	}

	public function write()
	{
		if(file_exists($this->path))
			$oldContents = parse_ini_file($this->path, true);

		if($oldContents == $this->content)
			return true;

		$string = '; File generated by BentoBase IniFile' . PHP_EOL;
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

	protected function writeValue($value)
	{
		if($value === true)
		{
			return 'true';

		}elseif($value === false){

			return 'false';

		}elseif(is_numeric($value)){

			return $value;

		}else{

			return '"' . str_replace("\n", "\\\n", $value) . '"';

		}
	}

	public function getArray($section = false)
	{
		return ($section) ? $this->content[$section] : $this->content;
	}

	public function get($section, $name)
	{
		return $this->content[$section][$name];
	}

	public function set($section, $name, $value)
	{
		$this->content[$section][$name] = $value;
	}

	public function clear()
	{
		$this->content = array();
	}
}


?>