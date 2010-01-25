<?php

class ModelActionLocationBasedThemePreview extends ModelActionLocationBasedRead
{
	public $htmlSettings = array( 'headerTitle' => 'Theme Preview' );

	public static $requiredPermission = 'Admin';

	public function viewHtml($page)
	{
		$query = Query::getQuery();
		$location = new Location($query['location']);

		$defaultTemplate = $location->getMeta('pageTemplate')
			? $location->getMeta('pageTemplate') . '.html'
			: 'index.html';

		$defaultTheme = $location->getMeta('htmlTheme');

		$template = isset($query['template']) ? $query['template'] : $defaultTemplate;
		$theme = isset($query['theme']) ? $query['theme'] : $defaultTheme;

		$page->setTemplate($template, $theme);
		$this->setTitle($this->htmlSettings['headerTitle'] . " ($theme : $template)");

		return parent::viewHtml($page);
	}
}

?>