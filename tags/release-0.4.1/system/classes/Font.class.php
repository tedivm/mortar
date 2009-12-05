<?php

class Font extends ContentBase
{

	protected $contentType = 'font';

	public function __construct($name)
	{
		$config = Config::getInstance();
		$this->name = $name;
		$this->url = ActiveSite::getLink('fonts') . $name . '/';
		$fontPath = $config['path']['fonts'] . $name . '/';
		$this->contentPath = $fontPath;

		$cache = new Cache('font', $this->name, ActiveSite::getLink('font'));
		$data = $cache->getData();

		if($cache->isStale())
		{

			$settingsPath = $this->contentPath . 'settings.ini';

			if(is_readable($settingsPath))
			{
				$iniFile = new IniFile($settingsPath);
				$data['settings'] = $iniFile->getArray();
			} else {
				$stylesheetPath = $this->contentPath . 'stylesheet.css';
				
				if(is_readable($stylesheetPath))
					$data['settings'] = $this->processStylesheet($stylesheetPath);
			}

			$cache->storeData($data);
		}

		if(isset($data['settings']))
			$this->settings = $data['settings'];
	}

	protected function processStylesheet($path)
	{
		$fontData = array();
		$fonts = array();

		$stylesheetRaw = file($path, FILE_IGNORE_NEW_LINES);
		$stylesheetRegex  =
	"/local\('(.*?)'\), local\('(.*?)'\), url\('(.*?)'\).{17}url\('(.*?)'\) format\('(.*?)'\), url\('(.*?)'\)/";

		$x = 0;
		while(isset($stylesheetRaw[$x]) && strpos($stylesheetRaw[$x], '* @') === false &&
			strpos($stylesheetRaw[$x], '*/') === false)
			$x++;

		while(isset($stylesheetRaw[$x]) && $namePos = strpos($stylesheetRaw[$x], '* @')) {
			$namePos += 3;
			$nameEndPos = strpos($stylesheetRaw[$x], ':');
			$propName = substr($stylesheetRaw[$x], $namePos, $nameEndPos - $namePos);
			$propValue = ltrim(substr($stylesheetRaw[$x], $nameEndPos + 1));
			$fontData[$propName] = $propValue;
			$x++;
		}

		while(isset($stylesheetRaw[$x]) && strpos($stylesheetRaw[$x], '@font-face') === false)
			$x++;

		while(isset($stylesheetRaw[$x]) && strpos($stylesheetRaw[$x], '@font-face') !== false) {
			$x++;
			
			$eotPos = strpos($stylesheetRaw[++$x], "url('") + 5;
			$eotEndPos = strpos($stylesheetRaw[$x], "');");
			$eot = substr($stylesheetRaw[$x], $eotPos, $eotEndPos - $eotPos);

			$groups = array();
			preg_match($stylesheetRegex, $stylesheetRaw[++$x], $groups);

			if (isset($groups[0])) {
				$font = $groups[2];
				$fonts[] = $font;
				$fontData[$font]['fullname'] = $groups[1];
				$fontData[$font]['woff'] = $groups[3];
				$fontData[$font]['ttf'] = $groups[4];
				$fontData[$font]['type'] = $groups[5];
				$fontData[$font]['svg'] = $groups[6];
				$fontData[$font]['eot'] = $eot;
			}

			while(isset($stylesheetRaw[$x]) && strpos($stylesheetRaw[$x], '@font-face') === false)
				$x++;
		}

		$fontData['fonts'] = $fonts;

		return $fontData;
	}

	public function getCss()
	{
		$fontCss = '';
		foreach ($this->settings['fonts'] as $font) {
			if(isset($this->settings[$font]))
				$fontData = $this->settings[$font];
			else
				continue;

			if(!isset($fontData['fullname']) || !isset($fontData['eot']) || !isset($fontData['woff']) ||
			   !isset($fontData['ttf']) || !isset($fontData['type']) || !isset($fontData['svg']))
			   	continue;

			$name = str_replace(' ', '', $fontData['fullname']);
			$url = $this->url;

			$fontCss .= "@font-face {\n";
			$fontCss .= "	font-family: '$name';\n";
			$fontCss .= "	src: url('$url{$fontData['eot']}');\n";
			$fontCss .= "	src: local('{$fontData['fullname']}'), local('$font'), ";
			$fontCss .= "url('$url{$fontData['woff']}') format('woff'), ";
			$fontCss .= "url('$url{$fontData['ttf']}') format('{$fontData['type']}'), ";
			$fontCss .= "url('$url{$fontData['svg']}') format('svg');\n";
			$fontCss .= "}\n\n\n";
		}
		
		return $fontCss;
	}

}

?>
