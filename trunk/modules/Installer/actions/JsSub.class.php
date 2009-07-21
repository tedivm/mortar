<?php

class InstallerActionJsSub implements ActionInterface
{

	public function __construct($identifier, $handler)
	{
		$this->ioHandler = $handler;
	}

	public function start()
	{
		//http://localhost/BentoBase/index.php?p=direct/module/BentoBase/Minify/bbAdmin-65b02d8f.js

	}

	public function viewAdmin($page)
	{
		echo $this->viewDirect();
		exit();
	}

	public function viewDirect()
	{
		$this->ioHandler->addHeader('Content-Type', 'application/x-javascript; charset=utf-8');
		$page = ActivePage::getInstance();
		$theme = $page->getTheme();
		$minifier = $theme->getMinifier('js');
		return $minifier->getBaseString();
	}

	public function checkAuth($action = NULL)
	{
		return true;
	}

}


?>