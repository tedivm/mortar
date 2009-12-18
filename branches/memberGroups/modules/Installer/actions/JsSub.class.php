<?php

class InstallerActionJsSub implements ActionInterface
{

	public function __construct($identifier, $handler)
	{
		$this->ioHandler = $handler;
	}

	public function start()
	{

	}

	public function viewAdmin($page)
	{
		echo $this->viewDirect();
		exit();
	}

	public function viewDirect()
	{
		$query = Query::getQuery();
		$this->ioHandler->addHeader('Content-Type', 'application/x-javascript; charset=utf-8');
		$page = ActivePage::getInstance();
		$theme = $page->getTheme();
		$minifier = $theme->getMinifier(strtolower($query['format']));
		return $minifier->getBaseString();
	}

	public function viewJs()
	{
		return $this->viewDirect();
	}

	public function viewCss()
	{
		return $this->viewDirect();
	}

	public function checkAuth($action = NULL)
	{
		return true;
	}

}


?>