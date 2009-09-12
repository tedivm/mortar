<?php

class ReadDisplayList implements DisplayList {

	protected $baseActionList = array('Read', 'Edit', 'Delete');

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
				$type = $model->getType();
				$template = $this->theme->getModelTemplate('Listing.html', $type);
				$htmlConverter = $model->getModelAs('Html');
				$htmlConverter->useTemplate($template);
				if($modelDisplay = $htmlConverter->getOutput())
				{
					$listingHtml->insertNewHtmlObject('div')->
						property('name', 'listing-container-child-' . $x)->
						addClass('modelListing')->addClass($type)->
						wrapAround($modelDisplay);
					$x++;
				}
			}
			$output = (string) $listingHtml;
		}
		return $output;
	}
}

?>