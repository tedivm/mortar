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
 * This class takes an action, runs it, and then formats the output for the admin side of the website.
 *
 * @package System
 * @subpackage RequestWrapper
 */
class AdminOutputController extends AbstractOutputController
{
	protected $permission = 'Admin';

	/**
	 * On start set the active resource to the ActivePage, set the theme, and add the navigation filters.
	 *
	 */
	protected function start()
	{
		$page = ActivePage::getInstance();

		$this->activeResource = $page;

		// Add filter to fit content into adminContent sub templates
		$contentFilter = new AdminControllerContentFilter();
		$this->addContentFilter($contentFilter);

		if(INSTALLMODE)
		{
			$page->setTemplate('index.html', 'bbAdmin');
		}else{
			$query = Query::getQuery();
			if(isset($query['location'])) {
				$location = Location::getLocation($query['location']);
			} else {
				$site = ActiveSite::getSite();
				$location = $site->getLocation();
			}

			$page->setTemplate('index.html', $location->getMeta('adminTheme'));
		}
	}

	/**
	 * This function takes a string (the output of the action) and gives it to the ActivePage as a replacement for the
	 * 'content' section of the theme template.
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

// content filters

/**
 * This class expands and allows for customization of the content that gets sent back by the running action.
 *
 * @package System
 * @subpackage RequestWrapper
 */
class AdminControllerContentFilter
{
	/**
	 * This takes the content area and expands it to include more information, such as the title and subtitle, of
	 * the action. The structure for that expanded display is written to the 'adminContent.html' theme file.
	 *
	 * @param OutputController $adminController
	 * @param string $output
	 * @return string
	 */
	public function update($adminController, $output)
	{
		$action = $adminController->getAction();
		$page = $adminController->getResource();

		$themePath = $page->getThemePath();
		$theme = $page->getTheme();

		if(method_exists($action, 'getName')) {
			$actionName = $action->getName();
			$page->addRegion('action', $actionName);
		} else {
			$actionName = '';
		}

		$processedOutput = new ViewThemeTemplate($theme,
			array('support/adminContent' . $actionName . '.html', 'support/adminContent.html') );

		$title = (isset($action->adminSettings['headerTitle'])) ? $action->adminSettings['headerTitle'] : '';
		$title .= (isset($action->adminSettings['useRider']) && $action->adminSettings['useRider']
			&& isset($action->adminSettings['titleRider'])) ? $action->adminSettings['titleRider'] : '';
		$subtitle = isset($action->adminSettings['headerSubTitle'])
			? $action->adminSettings['headerSubTitle']
			: '';

		$oldtitle = $page->getTitle();
		if(isset($oldtitle)) {
			$title = $oldtitle;
		} else {
			$page->setTitle($title);
		}

		$processedOutput->addContent(	array(	'display' => $output,
							'title' => $title,
							'subtitle' => $subtitle,
							'action' => $actionName));

		$output = $processedOutput->getDisplay();

		return $output;
	}
}

// resource filters

?>