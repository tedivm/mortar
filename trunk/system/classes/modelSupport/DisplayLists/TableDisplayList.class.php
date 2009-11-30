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
class TableDisplayList extends TemplateDisplayList {

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
		$actionUrls = $this->getActionList($model, $this->format);

		$modelActions = $this->getActionIcons($actionUrls, $model);

		$table->addField('model_actions',
			isset($modelActions) && $modelActions != '' ? "<ul class='action_list'>" . $modelActions . "</ul>" : "");
	}

}

?>