<?php


class HtmlOutputController extends AbstractOutputController
{
	protected function start()
	{
		$this->ioHandler->addHeader('Content-Type', 'text/html');


		$page = ActivePage::getInstance();
		$page->addRegion('title', 'BentoBase');
		$page->setTemplate('index.html', 'default');

		$this->activeResource = $page;

		// Add filter to fit content into adminContent sub templates
		$contentFilter = new HtmlControllerContentFilter();
		$this->addContentFilter($contentFilter);

	}

	protected function bundleOutput($output)
	{
		$this->activeResource->addRegion('content', $output);
	}

	protected function makeDisplayFromResource()
	{
		return $this->activeResource->makeDisplay();
	}

}

class HtmlControllerContentFilter
{
	public function update($htmlController, $output)
	{
		$action = $htmlController->getAction();
		$page = $htmlController->getResource();

		return $output;
	}
}


?>