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
class ViewTableDisplayList extends ViewTemplateDisplayList {

	protected $tableColumns;
	protected $modelData;
	protected $useIndex = true;
	protected $indexBase = 0;
	protected $sortable = true;
	protected $table;

	protected $allowedColumns = array('type' 	=> 'Type',
					'name' 		=> 'Name',
					'memgroup_name' => 'Name',
					'title' 	=> 'Title',
					'email'		=> 'Email Address',
					'membergroups'	=> 'Groups',
					'status'	=> 'Status',
					'owner'		=> 'Owner',
					'createdOn'	=> 'Created',
					'lastModified'	=> 'Last Modified',
					'publishDate'	=> 'Published');

	protected $sortableColumns = array('type', 'name', 'memgroup_name', 'email', 'status', 'owner', 
						'createdOn', 'lastModified', 'publishDate');

	protected $specialColumns = array();

	protected $listActions = true;

	public function __construct(Model $mmodel, array $modelList, $columns = null)
	{
		parent::__construct($mmodel, $modelList);		

		if(isset($columns) && is_array($columns)) {
			$this->allowedColumns = $columns;
		}

		$this->extractTableData();
	}

	public function useIndex($use, $base = 0)
	{
		if($use) {
			$this->useIndex = true;
		} else {
			$this->useIndex = false;
		}

		$this->indexBase = $base;
	}

	public function sortable($sort)
	{
		if(sort) {
			$this->sortable = true;
		} else {
			$this->sortable = false;
		}
	}

	public function setIndexBase($base)
	{
		$this->indexBase = $base;
	}

	public function setColumns($columns)
	{
		if(is_array($columns)) {
			$this->allowedColumns = $columns;
		}

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
					if (in_array($propName, $this->specialColumns)) {
						$this->modelData[$x][$propName] = $this->processSpecialColumn($propName, $propData);
					} elseif ($propName === 'owner') {
						$this->modelData[$x][$propName] = $propData['name'];
					} elseif (($propName === 'createdOn') || ($propName === 'lastModified') || 
						($propName === 'publishDate')) {
						$this->modelData[$x][$propName] = 
							date($this->indexDateFormat, $propData);
					} elseif ($propName === 'membergroups') {
						$this->modelData[$x][$propName] = $this->formatGroups($propData);
					} else {
						$this->modelData[$x][$propName] = $propData;
					}
				}
			}
			$x++;
		}
		$this->tableColumns = $columnList;
	}

	protected function processSpecialColumn($name, $data)
	{
		return $data;
	}

	public function getDisplay()
	{
		return $this->getListing();
	}

	/**
	 * Using the previously dictated model list and page, produces an Html listing in the Index style.
	 *
	 * @return String
	 */
	public function getListing()
	{
		if(count($this->modelList) === 0)
			return "<p>There were no matches for the specified query.</p>";

		if(isset($this->table))
			return $this->table->makeHtml();

		$this->generateTable();

		return $this->table->makeHtml();
	}

	public function generateTable()
	{
		$themeSettings = $this->theme->getSettings();
		
		$name = method_exists($this->model, 'getLocation')	? $this->model->getLocation()->getName()
									: $this->model->getType();

		$table = new Table($name . '_listing');
		$table->addClass('model-listing');
		$table->addClass('index-listing');
		$table->addClass($name . '-listing');
		$table->enableIndex($this->useIndex, $this->indexBase);

		$this->addColumnsToTable($table);

		$x = 0;
		foreach($this->modelList as $model)
		{
			$table->newRow();
			$this->addModelToTable($table, $this->modelData[$x++]);
			if($this->listActions)
				$this->addModelActionsToRow($table, $model);
		}

		$this->table = $table;
	}

	protected function addColumnsToTable($table)
	{
		$url = Query::getUrl();
		$query = Query::getQuery();

		$iconset = $this->theme->getIconset();
		if($iconset) {
			$up = $iconset->getIcon('upbutton', null, '(^)');
			$down = $iconset->getIcon('downbutton', null, '(v)');
		} else {
			$up = '(^)';
			$down = '(v)';
		}

		foreach ($this->tableColumns as $name => $label) {
			$finalLabel = $label;
			if($this->sortable && in_array($name, $this->sortableColumns)) {
				$sortUrl = clone($url);
				$sortUrl->browseBy = $name;

				if(isset($query['browseBy']) && $query['browseBy'] == $name) {
					if(isset($query['order']) && $query['order'] == 'desc') {
						$sortUrl->order = 'asc';
						$finalLabel .= ' ' . $down;
					} else {
						$sortUrl->order = 'desc';
						$finalLabel .= ' ' . $up;
					}
				}

				$finalLabel = $sortUrl->getLink($finalLabel);
			}

			$table->addColumnLabel('model_' . $name, $finalLabel);
		}

		if($this->listActions)
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

			if(!$group['is_system']) {
				if (!$first)
					$groupList .= ', ';
				else
					$first = false;
			
				$groupList .= $group['name'];
			}
		}
		
		return $groupList;
	}
}

?>
