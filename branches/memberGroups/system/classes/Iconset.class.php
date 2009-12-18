<?php

class Iconset extends ContentBase
{
	protected $contentType = 'iconset';
	protected $imagePath = '';


	/**
	 * Constructor takes the name of the iconset and loads the initial information
	 *
	 * @cache iconset *name *link
	 * @param string $name
	 */
	public function __construct($name)
	{
		$config = Config::getInstance();

		$this->name = $name;
		$this->url = ActiveSite::getLink('icons') . $name . '/';
		$iconsPath = $config['path']['icons'] . $name . '/';
		$this->contentPath = $iconsPath;
		$iconsUrl = $this->url;

		$cache = new Cache('iconset', $this->name, ActiveSite::getLink('icons'));
		$data = $cache->getData();

		if($cache->isStale())
		{
			$settingsPath = $this->contentPath . 'settings.ini';

			if(is_readable($settingsPath))
			{
				$iniFile = new IniFile($settingsPath);
				$data['settings'] = $iniFile->getArray();
			}

			$cache->storeData($data);
		}

		if(isset($data['settings']))
			$this->settings = $data['settings'];
	}

	/**
	 * Returns an HTML representation of the requested icon, either as an image or span-wrapped
	 * text. Accepts an optional $classes parameter to insert a set of classes into the
	 * generated HTML.
	 *
	 * @param string $name
	 * @param string|null $classes
	 */
	public function getIcon($name, $classes = null)
	{
		if (isset($classes))
			$classPhrase = "class='$classes'";
		else
			$classPhrase = '';

		if (isset($this->settings['images'][$name])) {
			$iconUrl = $this->getImageUrl($this->settings['images'][$name]);
			$icon = "<img src='$iconUrl' $classPhrase alt='$name' title='$name' />";
		} else {
			$icon = "<span $classphrase >$name</span>";
		}

		return $icon;
	}
}

?>
