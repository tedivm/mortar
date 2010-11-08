<?php


class ViewStringTemplate extends ViewThemeTemplate
{
	protected $twigLoader = 'TwigIntegrationStringLoader';


	public function __construct($template)
	{
		$this->name = $template;
	}

	protected function getTwigEnvironment()
	{
		$config = Config::getInstance();

		$options = $this->checkOptions($config['path']['temp'] . '/twigCache/strings' . $this->cacheSubdirectory);

		$loaderClass = $this->twigLoader;
		$loader = new $loaderClass();
		$twig = new Twig_Environment($loader, $options);
		return $twig;
	}

}

?>