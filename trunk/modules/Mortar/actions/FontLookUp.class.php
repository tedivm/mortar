<?php

class MortarActionFontLookUp extends ActionBase
{
	public static $requiredPermission = 'Read';

	protected $fontList = array();

	public function logic()
	{
		$fonts = array();
		$config = Config::getInstance();
		$fontPath = $config['path']['fonts'];
		$installedFonts = glob($fontPath . "*", GLOB_ONLYDIR);
		foreach($installedFonts as $fontPath) {
			$fontFamily = array_reverse(explode('/', $fontPath));
			$fonts[] = $fontFamily[0];
		}

		$cache = new Cache('fonts', 'names', 'all');
		$data = $cache->getData();

		if($cache->isStale())
		{
			$data = array();

			foreach($fonts as $fontFamily)
			{
				$font = new Font($fontFamily);
				$fontNames = $font->getNames();
				$data = array_merge($fontNames, $data);
			}

			$cache->storeData($data);
		}

		$this->fontList = $data;
	}

	public function viewCss()
	{
		$this->ioHandler->addHeader('Content-Type', 'text/css; charset=utf-8');
		$fb = new TagBoxFonts();
		return $fb->all . "\n\n\n";
	}

	public function viewJs()
	{
		$this->ioHandler->addHeader('Content-Type', 'application/x-javascript; charset=utf-8');
		$results  = "CKEDITOR.editorConfig = function( config )\n{\n";
		$results .= "config.font_names='";
		foreach($this->fontList as $font) 
			$results .= $font . "; ";
		$results .= "'+ config.font_names; \n};";

		return $results;
	}

}

?>