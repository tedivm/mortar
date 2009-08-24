<?php

class Minifier
{
	protected $type;
	protected $checkSum;
	protected $initialCheckSum;
	protected $paths = array();
	protected $scriptString;

	public function __construct($type = 'js')
	{
		$this->type = $type == 'js' ? 'js' : 'css';
	}

	public function minifyFiles()
	{
		if (!$string = $this->getBaseString())
			return false;
		if($this->type == 'js')
		{
			$output = JSMin::minify($string);
		}elseif($this->type == 'css'){
			$output = cssmin::minify($string);
		}

		$this->checkSum = hash('crc32', $output);
		return $output;
	}

	public function getBaseString()
	{
		if(!isset($this->scriptString))
			if(!$this->processFiles())
				return false;
		return $this->scriptString;
	}

	public function addFiles($files)
	{
		$this->paths = array_merge($this->paths, $files);
	}

	public function getChecksum()
	{
		if(!isset($this->checkSum))
			return false;

		return $this->checkSum;
	}

	public function getInitialChecksum()
	{
		if(!isset($this->scriptString))
			if(!$this->processFiles())
				return false;

		if(!isset($this->initialCheckSum))
			return false;

		return $this->initialCheckSum;
	}

	protected function processFiles()
	{
		$bigFile = '';
		foreach($this->paths as $path)
		{
			if(!is_readable($path))
				continue;


			$bigFile .= PHP_EOL  . '/* ' . $path . ' */' . PHP_EOL;
			$bigFile .= file_get_contents($path) . PHP_EOL . PHP_EOL;
		}

		if($bigFile == '')
			return false;

		$this->scriptString = $bigFile;
		$this->initialCheckSum = hash('crc32', $bigFile);
		return true;
	}
}
