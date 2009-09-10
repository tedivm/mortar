<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage ModelSupport
 */

/**
 * This class takes an array of models and transforms it into HTML output in the style of an administrative Index list.
 *
 * @package System
 * @subpackage ModelSupport
 */
class IndexDisplayList implements DisplayList {

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
	 * @param Page $page
	 */
	public function __construct(Model $mmodel, array $modelList, Page $page)
	{
		$this->model = $mmodel;
		$this->modelList = $modelList;
		$this->page = $page;
		$this->theme = $this->page->getTheme();

		$query = Query::getQuery();
		$this->format = $query['format'];
	}

	/**
	 * Using the previously dictated model list and page, produces an Html listing in the Index style.
	 *
	 * @return String
	 */
	public function getListing()
	{
		$themeSettings = $this->theme->getSettings();

		$table = new Table($this->model->getLocation()->getName() . '_listing');
		$table->addClass('model-listing');
		$table->addClass('index-listing');
		$table->addClass($this->model->getLocation()->getName() . '-listing');
		$table->enableIndex();

		$this->addColumnsToTable($table);

		foreach($this->modelList as $model)
		{
			$table->newRow();
			$this->addModelToTable($table, $model);
			$this->addModelActionsToRow($table, $model);
		}

		return $table->makeHtml();
	}

	protected function addColumnsToTable($table)
	{
		$table->addColumnLabel('model_type', 'Type');
		$table->addColumnLabel('model_name', 'Name');
		$table->addColumnLabel('model_title', 'Title');
		$table->addColumnLabel('model_status', 'Status');
		$table->addColumnLabel('model_owner', 'Owner');
		$table->addColumnLabel('model_creationTime', 'Created');
		$table->addColumnLabel('model_lastModified', 'Last Modified');
		$table->addColumnLabel('model_actions', 'Actions');
	}

	protected function addModelToTable($table, Model $model)
	{
		$location = $model->getLocation();
		$owner = $location->getOwner();
		$createdOn = $location->getCreationDate();
		$modifiedOn = $location->getLastModified();
		$type = $model->getType();
		$table->addField('model_type', $model->getType());
		$table->addRowClass($type . '_item');

		$location = $model->getLocation();
		$url = new Url();
		$url->location = $location->getId();
		$url->format = $this->format;


		$table->addField('model_name',
				 isset($model->name) ? "<a href='" . $url . "'>" . $model->name . "</a>" : "");
		$table->addField('model_title', isset($model['title']) ? $model['title'] : "");
		$table->addField('model_status', isset($model->status) ? $model->status : "");
		$table->addField('model_owner', ($owner && $owner['name']) ? $owner['name'] : "");
		$table->addField('model_creationTime', date($this->indexDateFormat, $createdOn));
		$table->addField('model_lastModified', date($this->indexDateFormat, $modifiedOn));
	}

	protected function addModelActionsToRow($table, $model)
	{
		$themeSettings = $this->theme->getSettings();
		$modelActions = '';
		$themeUrl = $this->theme->getUrl();
		$location = $model->getLocation();

		$baseUrl = new Url();
		$baseUrl->locationId = $location->getId();
		$baseUrl->format = $this->format;

		$actionTypes = array('Read', 'Edit', 'Delete');
		if(isset($location) && $location->hasChildren())
			array_push($actionTypes, 'Index');

		$allowedActionTypes = array();
		$user = ActiveUser::getUser();
		$userId = $user->getId();
		$actionList = '';
		foreach($actionTypes as $action)
		{
			$modelListAction = new DisplayMaker();
			$modelListAction->setDisplayTemplate("<li class='action action_$action'>{# action #}</li>");
			$actionUrl = clone $baseUrl;
			$actionUrl->action = $action;

			if($actionUrl->checkPermission($userId))
			{
				$actionDisplay = (isset($themeSettings['images']['action_images']) && $themeSettings['images']['action_images'] == true) ?
						 '<img class="tooltip action_icon ' . $action . '_icon" title="' . $action . '" alt="' . $action . '" src="' .
						 $themeUrl . $themeSettings['images'][$action . '_image'] . '" />' : $action;

				$modelActions .= '<li class="action action_' . $action . '">' . $actionUrl->getLink($actionDisplay) . '</li>';
			}
		}

		$table->addField('model_actions',
			isset($modelActions) && $modelActions != '' ? "<ul class='action_list'>" . $modelActions . "</ul>" : "");
	}

}

?>