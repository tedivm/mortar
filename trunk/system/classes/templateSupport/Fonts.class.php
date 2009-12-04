<?php

class TagBoxFonts
{

	protected $fontList = array();
	protected $decs = array();

	public function __construct()
	{
		$config = Config::getInstance();
		$fontPath = $config['path']['fonts'];
		$installedFonts = glob($fontPath . "*", GLOB_ONLYDIR);
		foreach($installedFonts as $fontPath) {
			$fontName = array_reverse(explode('/', $fontPath));
			$this->fontList[] = $fontName[0];
		}
	}

	protected function getAtDeclarations()
	{
		$cache = new Cache('fonts', 'tagbox');
		$data = $cache->getData();

		if($cache->isStale())
		{
			foreach($this->fontList as $fontName)
			{
				$font = new Font($fontName);
				$data[$fontName] = $font->getCss();
			}

			$cache->storeData($data);
		}

		$decs = '';
		
		foreach ($data as $css)
			$decs .= $css;

		return $decs;
		
	}

	public function __get($tagname)
	{
		return ($tagname === 'all') ? $this->getAtDeclarations() : false;
	}

	public function __isset($tagname)
	{
		return ($tagname === 'all') ? true : false;
	}
}

?>
