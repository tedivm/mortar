<?php

class AltamiraMortarChart
{
	protected $chart;

	public function __construct($name = null)
	{
		$this->chart = new AltamiraChart($name);
	}

	public function getDiv()
	{
		$page = ActivePage::getInstance();
		$page->addStartupScript($this->chart->getScript());
		$url = new Url();
		$path = (string) $url . 'modules/Altamira/jqplot/plugins/';
		$files = $this->chart->getFiles();
		foreach($files as $file) {
			$page->addJSInclude($path . $file);
		}

		return $this->chart->getDiv();
	}

	public function __call($fnc, $args)
	{
		return call_user_func_array(array($this->chart, $fnc), $args);
	}
}

?>