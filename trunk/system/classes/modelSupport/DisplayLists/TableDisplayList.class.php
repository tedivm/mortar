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

	protected $tableColumns;
	protected $modelData;

	protected $allowedColumns = array('type' 	=> 'Type',
					'name' 		=> 'Name',
					'title' 	=> 'Title',
					'email'		=> 'Email Address',
					'membergroups'	=> 'Groups',
					'status'	=> 'Status',
					'owner'		=> 'Owner',
					'createdOn'	=> 'Created',
					'lastModified'	=> 'Last Modified',
					'publishDate'	=> 'Published');

	public function __construct(Model $mmodel, array $modelList)
	{
		parent::__construct($mmodel, $modelList);		

		$this->extractTableData();
	}

	protected function extractTableData()
	{
		$columnList = array();
		$x = 0;

		foreach ($this->modelList as $model)
		{
			$properties = $model->__toArray();
			
			foreach($this->allowedColumns as $propName => $propLabel) {
				if (isset($properties[$propName])) {
					$propData = $properties[$propName];
					$columnList[$propName] = $propLabel;
					if ($propName === 'owner')
						$this->modelData[$x][$propName] = $propData['name'];
					elseif (($propName === 'createdOn') || ($propName === 'lastModified') ||
						($propName === 'publishDate'))
						$this->modelData[$x][$propName] = 
							date($this->indexDateFormat, $propData);
					elseif ($propName === 'membergroups')
						$this->modelData[$x][$propName] = $this->formatGroups($propData);
					else
						$this->modelData[$x][$propName] = $propData;
				}
			}
			$x++;
		}
		$this->tableColumns = $columnList;
	}

	/**
	 * Using the previously dictated model list and page, produces an Html listing in the Index style.
	 *
	 * @return String
	 */
	public function getListing()
	{
		$themeSettings = $this->theme->getSettings();
		
		$name = method_exists($this->model, 'getLocation')	? $this->model->getLocation()->getName()
									: $this->model->getType();

		$table = new Table($name . '_listing');
		$table->addClass('model-listing');
		$table->addClass('index-listing');
		$table->addClass($name . '-listing');
		$table->enableIndex();

		$this->addColumnsToTable($table);

		$x = 0;
		foreach($this->modelList as $model)
		{
			$table->newRow();
			$this->addModelToTable($table, $this->modelData[$x++]);
			$this->addModelActionsToRow($table, $model);
		}

		return $table->makeHtml();
	}

	protected function addColumnsToTable($table)
	{
		foreach ($this->tableColumns as $name => $label) 
			$table->addColumnLabel('model_' . $name, $label);

		$table->addColumnLabel('model_actions', 'Actions');
	}

	protected function addModelToTable($table, $modelArray)
	{
		foreach($this->tableColumns as $name => $label)
			if(isset($modelArray[$name]))
				$table->addField('model_' . $name, $modelArray[$name]);
			else
				$table->addField('model_' . $name, '');
	}

	protected function addModelActionsToRow($table, $model)
	{
		$actionUrls = $this->getActionList($model, $this->format);

		$modelActions = $this->getActionIcons($actionUrls, $model);

		$table->addField('model_actions',
			isset($modelActions) && $modelActions != '' ? "<ul class='action_list'>" . $modelActions . "</ul>" : "");
	}

	protected function formatGroups($groups)
	{
		$first = true;
		$groupList = '';

		foreach($groups as $groupId) { 
			$group = ModelRegistry::loadModel('MemberGroup', $groupId);

			if (!$first) 
				$groupList .= ', ';
			else
				$first = false;
			
			$groupList .= $group->name;
		}
		
		return $groupList;
	}
}

?>