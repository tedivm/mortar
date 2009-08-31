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
class IndexDisplayList {

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
	 * Simple constructor to set the key variables.
	 *
	 * @param Model $m
	 * @param array $models
	 * @param Page $p
	 */
	public function __construct(Model $m, array $models, Page $p)
	{
		$this->model = $m;
		$this->modelList = $models;
		$this->page = $p;
	}

	/**
	 * Using the previously dictated model list and page, produces an Html listing in the Index style.
	 *
	 * @return String
	 */
	public function getListing() {
	
		$theme = $this->page->getTheme();
		$themeSettings = $theme->getSettings();

		$table = new Table($this->model->getLocation()->getName() . '_listing');
		$table->addClass('model-listing');
		$table->addClass('index-listing');
		$table->addClass($this->model->getLocation()->getName() . '-listing');
		$table->enableIndex();

		$table->addColumnLabel('model_type', 'Type');
		$table->addColumnLabel('model_name', 'Name');
		$table->addColumnLabel('model_title', 'Title');
		$table->addColumnLabel('model_owner', 'Owner');
		$table->addColumnLabel('model_creationTime', 'Created');
		$table->addColumnLabel('model_lastModified', 'Last Modified');
		$table->addColumnLabel('model_actions', 'Actions');

		foreach($this->modelList as $model)
		{
			$modelActions = '';
			$modelData = $model->getModelAs('Html');
			$table->newRow();
			$modelProperties = $modelData->getProperties();
			$table->addField('model_type',
					 isset($modelProperties['model_type']) ? $modelProperties['model_type'] : "");
			$table->addField('model_name',
					 isset($modelProperties['model_name']) ? "<a href='" . $modelProperties['permalink'] . "'>" . $modelProperties['model_name'] . "</a>" : "");
			$table->addField('model_title',
					 isset($modelProperties['model_title']) ? $modelProperties['model_title'] : "");
			$table->addField('model_owner',
					 isset($modelProperties['model_owner']) ? $modelProperties['model_owner'] : "");
			$table->addField('model_creationTime',
					 isset($modelProperties['model_creationTime']) ? date($this->indexDateFormat, $modelProperties['model_creationTime']) : "");
			$table->addField('model_lastModified',
					 isset($modelProperties['model_lastModified']) ? date($this->indexDateFormat, $modelProperties['model_lastModified']) : "");

			foreach($modelProperties['model_action_list'] as $action) 
			{
				$actionDisplay = (isset($themeSettings['images']['action_images']) && $themeSettings['images']['action_images'] == true) ?
						'<img alt="' . $action . '" src="' . $theme->getUrl() . $themeSettings['images'][$action . '_image'] . '" />' : $action; 
				$modelActions .= '<li class="action action_' . $action . '"><a href="' . $modelProperties['model_action_' . $action] . '">' . $actionDisplay . '</a></li>'; 
			}
				
			$table->addField('model_actions',
					 isset($modelProperties['model_actions']) ? "<ul class='action_list'>" . $modelActions . "</ul>" : "");
			

		}

		return $table->makeHtml();	
	}

}

?>