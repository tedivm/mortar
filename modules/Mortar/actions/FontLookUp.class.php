<?php

class MortarActionFontLookUp extends ActionBase
{
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
		$fb = new TagBoxFonts();
		return $fb->all;
	}

	public function viewJs()
	{
		$results  = "CKEDITOR.editorConfig = function( config )\n{\n";
		$results .= "config.font_names='";
		foreach($this->fontList as $font) 
			$results .= $font . "; ";
		$results .= "'+ config.font_names; \n};";

		return $results;
	}

}

?>