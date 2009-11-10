<?php

class ReadDisplayList implements DisplayList {


	protected $baseActionList = array('Read', 'Edit', 'Delete', 'Index');

	/**
	 * This is the date format used when converting the model to an html table.
	 *
	 * @var string
	 */
	protected $indexDateFormat = 'm.d.y g:i a';

	/**
	 * Model representing the location of which the index is being taken.
	 *
	 * @var Model
	 */
	protected $model;

	/**
	 * A list of models to be iteratively processed for display.
	 *
	 * @var array
	 */
	protected $modelList;

	/**
	 * Page instance for displaying the Html.
	 *
	 * @var Page
	 */
	protected $page;

	/**
	 * This is the Theme object associated with the current page.
	 *
	 * @var Theme
	 */
	protected $theme;

	/**
	 * This is the format currently being used (this is primarily for URLs)
	 *
	 * @var string
	 */
	protected $format;

	/**
	 * Simple constructor to set the key variables.
	 *
	 * @param Model $mmodel
	 * @param array $modelList
	 */
	public function __construct(Model $mmodel, array $modelList)
	{
		$this->model = $mmodel;
		$this->modelList = $modelList;

		$query = Query::getQuery();
		$this->format = $query['format'];
	}

	public function addPage(Page $page)
	{
		$this->page = $page;
		$this->theme = $this->page->getTheme();
	}


	public function getListing()
	{
		if(count($this->modelList) > 0)
		{
			$listingHtml = new HtmlObject('div');
			$listingHtml->addClass('listing-container');
			$x = 1;

			foreach($this->modelList as $model)
			{
				$location = $model->getLocation();
				$url = new Url();
				$url->location = $location->getId();
				$url->format = $this->format;
				$actionUrls = $this->getActionList($model, $this->format);

				$actionView = new ViewModelTemplate($this->theme, $model, 'Listing.html');
				$actionView->addContent(array('model_actions' => $this->getActionIcons($actionUrls, $model)));

				$htmlConverter = $model->getModelAs('Html');
				$htmlConverter->useView($actionView);
				if($modelDisplay = $htmlConverter->getOutput())
				{
					$listingHtml->insertNewHtmlObject('div')->
						property('name', 'listing-container-child-' . $x)->
						addClass('modelListing')->addClass($model->getType())->
						addClass('listing-container-child')->
						property('id', 'listing-container-child-' . $x)->
						wrapAround($modelDisplay);
					$x++;
				}
			}
			$output = (string) $listingHtml;
		}

		return isset($output) ? $output : false;
	}

	protected function getActionList($model, $format)
	{
		$baseActionTypes = $this->baseActionList;

		$actionUrls = $model->getActionUrls($format);
		$allowedUrls = array();

		foreach($baseActionTypes as $action) {
			if(isset($actionUrls[$action])) {
				$actionUrl = $actionUrls[$action];
				array_push($allowedUrls, array($action, $actionUrl));
			}
		}

		return $allowedUrls;
	}

	protected function getActionIcons(array $actionUrls, model $model = null)
	{
		$themeSettings = $this->theme->getSettings();
		$themeUrl = $this->theme->getUrl();
		if (isset($model)) $location = $model->getLocation();
		$modelActions = '';
		foreach($actionUrls as $action)
		{
			$fadeClass = ((isset($location)) && ($action[0] == 'Index') && !($location->getChildren())) ? 'iconFade' : '';
			$actionDisplay = (isset($themeSettings['images']['action_images']) && $themeSettings['images']['action_images'] == true) ?
					 '<img class="tooltip action_icon ' . $fadeClass . ' ' . $action[0] . '_icon" title="' . $action[0] . '" alt="' . $action[0] . 
					 '" src="' . $themeUrl . $themeSettings['images'][$action[0] . '_image'] . '" />' : $action[0];

			$modelActions .= '<li class="action action_' . $action . '">' . $action[1]->getLink($actionDisplay) . '</li>';
		}

		return $modelActions;
	}

}

?>