<?php

abstract class ContentBase
{

	/**
	 * name of the current theme or content set
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Url for the active website
	 *
	 * @access protected
	 * @var string
	 */
	protected $url;

	/**
	 * Directory of the current instance of content.
	 *
	 * @var string
	 */
	protected $contentPath;

	/**
	 * Settings from the INI file in the theme folder.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * The type of content stored in the concrete class (theme, icons, etc.)
	 *
	 * @var string
	 */
	protected $contentType;

	/**
	 * The default path for images
	 *
	 * @access protected
	 * @var string
	 */
	protected $imagePath;

	/**
	 * Returns the theme-specific settings found in the settings.ini theme file.
	 *
	 * @return array
	 */
	public function getSettings()
	{
		return isset($this->settings) ? $this->settings : false;
	}

	/**
	 * Returns the file path to the theme.
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->contentPath;
	}

	/**
	 * Returns the name of the theme.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Returns the base url for the content
	 *
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * This function returns the Url for an image given its name.
	 *
	 * @param string $type js or css
	 * @return Minifier
	 */
	public function getImageUrl($imageName)
	{
		$cache = new Cache($this->contentType, $this->name, 'imagepath', $imageName);
		$url = $cache->getData();

		if($cache->isStale())
		{
			$imagePath = $this->contentPath . $this->imagePath . $imageName;

			if($realPath = realpath($imagePath))
			{
				if(!strpos($realPath, $this->contentPath . $this->imagePath) === 0)
					throw new CoreSecurity('Attempted to load image outside the image directory.');

				$themeUrl = $this->getUrl();
				$url = $themeUrl . $this->imagePath . $imageName;
			}elseif($parent = $this->getParentTheme()){
				$url = $parent->getImageUrl($imageName);
			}else{
				$url = false;
			}
			$cache->storeData($url);
		}
		return $url;
	}

	/**
	 * Returns all of the urls for the files in the requested directory
	 *
	 * @access protected
	 * @param string $path
	 * @param string $url This is the base url that the files are called from
	 * @param string $extention
	 * @return array
	 */
	protected function getFiles($path, $url, $extention = '.*', $defaultPriority = 30)
	{
		if(strlen($path) < 1 || strlen($url) < 1)
			return false;

		$pattern = glob($path . '*' . $extention);
		$fileArray = array();
		foreach($pattern as $file)
		{
			unset($priority);
			$tmpArray = explode('/', $file);
			$fileName = array_pop($tmpArray);
			$fileDetails = explode('.', $fileName);
			$min = false;
			$extension = array_pop($fileDetails);
			$library = array_shift($fileDetails);

			switch (count($fileDetails))
			{
				case 0:
					$name = $library;
					$library = 'none';
					break;

				case 2:
					$option = array_pop($fileDetails);
					if(is_numeric($option))
						$priority = $option;

				case 1:
					$name = array_pop($fileDetails);
					break;

			}

			$fileArray[$library][$name]['mainLink'] = $url . $fileName;
			$fileArray[$library][$name]['path']  = $file;
			$fileArray[$library][$name]['priority']  = isset($priority) ? $priority : $defaultPriority;
		}
		return $fileArray;
	}


	// I will fix this up later.
	public function getParentTheme()
	{
		return false;
	}
}

?>
