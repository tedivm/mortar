<?php


class ViewStringTemplate extends ViewThemeTemplate
{
	protected $twigLoader = 'Twig_Loader_String';


	public function __construct($template)
	{
		$this->name = $template;
	}

	protected function getTwigLoader()
	{
		$config = Config::getInstance();

		$cachePath = $config['path']['temp'] . '/twigCache/strings' . $this->cacheSubdirectory;

		$loaderClass = $this->twigLoader;
		$loader = new $loaderClass();
		$twig = new Twig_Environment($loader, array('cache' => $cachePath));
		return $twig;
	}

}

?>