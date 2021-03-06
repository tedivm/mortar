<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage RequestWrapper
 */

/**
 * This class takes an action, runs it, and then formats the output for the general user side of the website.
 *
 * @package System
 * @subpackage RequestWrapper
 */
class HtmlOutputController extends AbstractOutputController
{
	/**
	 * This sets the mime type to text/html.
	 *
	 * @var string
	 */
	public $mimeType = 'text/html; charset=UTF-8';

	/**
	 * This function sets the template, the active page, and adds the HtmlControllerContentFilter content filter.
	 *
	 */
	protected function start()
	{
		$page = ActivePage::getInstance();

		$query = Query::getQuery();
		if(isset($query['location'])) {
			$location = Location::getLocation($query['location']);
		} else {
			$site = ActiveSite::getSite();
			$location = $site->getLocation();
		}
		$template = array('index.html');
		if($pageTemplate = $location->getMeta('pageTemplate'))
			array_unshift($template, $pageTemplate . '.html');
		$page->setTemplate($template, $location->getMeta('htmlTheme'));

		$this->activeResource = $page;

		// Add filter to fit content into htmlContent sub templates
		$contentFilter = new HtmlControllerContentFilter();
		$this->addContentFilter($contentFilter);

	}

	/**
	 * This takes the text (in this case the output of the action) and adds it to the content area of the current
	 * active page.
	 *
	 * @param string $output
	 */
	protected function bundleOutput($output)
	{
		$this->activeResource->addRegion('content', $output);
	}

	/**
	 * This takes the activeResource (in this case the ActivePage) and returns the display of that page.
	 *
	 * @return string Html formatted
	 */
	protected function makeDisplayFromResource()
	{
		return $this->activeResource->makeDisplay();
	}

}

/**
 * This class expands and allows for customization of the content that gets sent back by the running action. It
 * currently doesn't do anything.
 *
 * @package System
 * @subpackage RequestWrapper
 */
class HtmlControllerContentFilter
{
	public function update($htmlController, $output)
	{
		$action = $htmlController->getAction();
		$page = $htmlController->getResource();

		$themePath = $page->getThemePath();
		$theme = $page->getTheme();

		if(method_exists($action, 'getName')) {
			$actionName = $action->getName();
		} else {
			$actionName = '';
		}

		$title = $action->getTitle('Html');

		$oldtitle = $page->getTitle();
		if(isset($oldtitle)) {
			$title = $oldtitle;
		} else {
			$page->setTitle($title);
		}

		$processedOutput = new ViewThemeTemplate($theme, 
			array('support/htmlContent' . $actionName . '.html', 'support/htmlContent.html') );
		$processedOutput->addContent(	array(	'display' => $output,
							'title' => $title,
							'action' => $actionName));
		$output = $processedOutput->getDisplay();

		return $output;
	}
}


?>