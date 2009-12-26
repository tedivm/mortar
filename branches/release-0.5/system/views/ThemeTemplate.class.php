<?php

class ViewThemeTemplate
{
	protected $twigLoader = 'TwigIntegrationThemeLoader';
	protected $twigEnvironment = 'TwigIntegrationThemeEnvironment';

	protected $theme;
	protected $name;
	protected $cacheSubdirectory = '';
	protected $content = array();

	public function __construct(Theme $theme, $name)
	{
		$this->theme = $theme;
		$this->name = $name;
	}

	public function addContent($array)
	{
		$this->content = array_merge($this->content, $array);
	}

	public function getDisplay()
	{
		try{
			$twig = $this->getTwigLoader();
			$template = $twig->loadTemplate($this->name);
			return $template->render($this->content);
		}catch(Exception $e){
			return false;
		}
	}

	protected function getTwigLoader()
	{
		$config = Config::getInstance();

		$basePaths = $this->getThemePaths();
		$basePaths['system'] = $config['path']['templates'];

		$loaderClass = $this->twigLoader;
		$loader = new $loaderClass();
		$loader->setPaths($basePaths);

		$options = $this->checkOptions($config['path']['temp'] . '/twigCache' . $this->cacheSubdirectory);

		$environmentClass = $this->twigEnvironment;
		$twig = new $environmentClass($loader, $options);
		return $twig;
	}

	protected function checkOptions($cachePath)
	{
		$options = array();

		if(!defined('DISABLECACHE') || DISABLECACHE !== true)
			$options['cache'] = $cachePath;
		else
			$options['cache'] = false;

		if(defined('REBUILD_TEMPLATES'))
			$options['auto_reload'] = REBUILD_TEMPLATES;
		else
			$options['auto_reload'] = true;

		return $options;
	}

	protected function getThemePaths()
	{
		$paths = array();
		$theme = $this->theme;
		do
		{
			$paths[$theme->getName()] = $theme->getPath();
		}while($theme = $theme->getParentTheme());

		return $paths;
	}

}

?>
