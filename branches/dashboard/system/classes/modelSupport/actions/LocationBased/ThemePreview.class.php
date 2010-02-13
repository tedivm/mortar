<?php

class ModelActionLocationBasedThemePreview extends ModelActionLocationBasedRead
{
	public $htmlSettings = array( 'headerTitle' => 'Theme Preview', 'useRider' => true);

	public static $requiredPermission = 'Admin';

	public function viewHtml($page)
	{
		$query = Query::getQuery();
		$location = isset($query['location'])
			? new Location($query['location'])
			: ActiveSite::getSite()->getLocation();
		$parent = $location->getParent();

		$defaultTemplate = $parent->getMeta('pageTemplate')
			? $parent->getMeta('pageTemplate') . '.html'
			: 'index.html';

		$defaultTheme = $location->getMeta('htmlTheme');

		$template = isset($query['template']) ? $query['template'] . '.html' : $defaultTemplate;
		$theme = isset($query['theme']) ? $query['theme'] : $defaultTheme;

		$page->setTemplate($template, $theme);
		$this->htmlSettings['titleRider'] = " ($theme : $template)");

		return parent::viewHtml($page);
	}
}

?>