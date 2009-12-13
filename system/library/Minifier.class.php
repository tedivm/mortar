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

	public function setBaseString($string)
	{
		$this->scriptString = $string;
		$this->initialCheckSum = md5($string);
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
 		/* Since we're merging the files together we want to make it easier for designers to find the css they're
		  looking for. However, we don't want to give out path data, which could be used to identify the os and other
		  crap, out to anyone looking so we're replacing those paths with useful, but non-identifying, path
		  abbreviations. At some point this will have to be changed around to allow this class to be used outside
		  of Mortar.
		*/
		$config = Config::getInstance();
		$config['path']['modules']; // theme javascript templates fonts icons

		$search = array();
		$replace = array();

		$search[] = $config['path']['modules'];
		$replace[] = 'mortar/modules/';

		$search[] = $config['path']['theme'];
		$replace[] = 'mortar/themes/';

		$search[] = $config['path']['javascript'];
		$replace[] = 'mortar/javascript/';

		$search[] = $config['path']['templates'];
		$replace[] = 'mortar/templates/';

		$search[] = $config['path']['fonts'];
		$replace[] = 'mortar/fonts/';

		$search[] = $config['path']['icons'];
		$replace[] = 'mortar/icons/';

		$bigFile = '';
		foreach($this->paths as $path)
		{
			if(!is_readable($path))
				continue;


			$bigFile .= PHP_EOL  . '/* ' . str_replace($search, $replace, $path) . ' */' . PHP_EOL;
			$bigFile .= file_get_contents($path) . PHP_EOL . PHP_EOL;
		}

		if($bigFile == '')
			return false;

		$this->setBaseString($bigFile);
		return true;
	}
}
?>