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
	protected $filterable = true;
	protected $repeatHeaders = false;
	protected $linkTitles = false;
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


	/**
	 * Defines fields which should not be sortable.
	 *
	 * @var array
	 */
	protected $dontSort = array('actions');
	protected $dontFilter = array('actions', 'createdOn', 'lastModified', 'publishDate', 'name', 'title');
	protected $filterValues = array();

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

	public function sortable($sort = true)
	{
		if($sort) {
			$this->sortable = true;
		} else {
			$this->sortable = false;
		}
	}

	public function filterable($filter = true)
	{
		if($filter) {
			$this->filterable = true;
		} else {
			$this->filterable = false;
		}
	}

	public function showActions($show = true)
	{
		if($show) {
			$this->listActions = true;
		} else {
			$this->listActions = false;
		}
	}

	public function linkTitles($link = true)
	{
		if($link) {
			$this->linkTitles = true;
		} else {
			$this->linkTitles = false;
		}	
	}

	public function setIndexBase($base)
	{
		$this->indexBase = $base;
	}

	public function getColumns()
	{
		return $this->allowedColumns;
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
		foreach($this->allowedColumns as $propName => $propLabel)
			$columnList[$propName] = false;

		$x = 0;

		foreach ($this->modelList as $model)
		{
			$properties = $model->__toArray();
			$titleLinked = false;

			foreach($this->allowedColumns as $propName => $propLabel) {
				if (isset($properties[$propName])) {
					$propData = $properties[$propName];
					$columnList[$propName] = $propLabel;
					if (in_array($propName, $this->specialColumns)) {
						$this->modelData[$x][$propName] = $this->processSpecialColumn($propName, $propData);
					} elseif(in_array($propName, array('designation', 'title')) && !$titleLinked) {
						$url = $model->getUrl();
						$link = $url->getLink($propData);
						$this->modelData[$x][$propName] = $link;
						$titleLinked = true;
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

					if(!isset($this->filterValues[$propName]))
						$this->filterValues[$propName] = array();

					if(!in_array($this->modelData[$x][$propName], $this->filterValues[$propName]))
						$this->filterValues[$propName][] = $this->modelData[$x][$propName];
				}
			}
			$x++;
		}
		foreach($columnList as $propName => $propLabel) {
			if($propLabel === false) {
				unset($columnList[$propName]);
			}
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
		$url = Query::getUrl();
		$current = (string) $url;
		unset($url->filter);
		$p = new HtmlObject('p');
		if((string) $url != $current) {
			$link = $url->getLink('Clear filters');
			$p->wrapAround($link);
		} 

		if(count($this->modelList) === 0)
			return $p . "<p>There were no matches for the specified query.</p>";

		if(isset($this->table))
			return $p . $this->table->makeHtml();

		$this->generateTable();

		return $p . $this->table->makeHtml();
	}

	public function generateTable()
	{
		$themeSettings = $this->theme->getSettings();
		
		$name = method_exists($this->model, 'getLocation')	? $this->model->getLocation()->getName()
									: $this->model->getType();

		$table = new Table($name . '_listing');
		$table->repeatHeader($this->repeatHeaders);
		$table->addClass('model-listing');
		$table->addClass('index-listing');
		$table->addClass($name . '-listing');
		$table->enableIndex($this->useIndex, $this->indexBase);

		$x = 0;
		foreach($this->modelList as $model)
		{
			$table->newRow();
			$this->addModelToTable($table, $this->modelData[$x++]);
			if($this->listActions)
				$this->addModelActionsToRow($table, $model);
		}

		$this->addColumnsToTable($table);

		$this->table = $table;
	}

	public function setFilterValues($values)
	{
		$this->filterValues = $values;
	}

	protected function addColumnsToTable($table)
	{
		$url = Query::getUrl();
		$query = Query::getQuery();

		if($iconset = $this->theme->getIconset()) {
			$up = $iconset->getIcon('upbutton', 'sort-asc-icon', '(^)');
			$down = $iconset->getIcon('downbutton', 'sort-desc-icon', '(v)');
			$on = $iconset->getIcon('dotblack', 'filter-dot-on', '(*)');
			$off = $iconset->getIcon('dotwhite', 'filter-dot-off', '( )');
		} else {
			$up = '(^)';
			$down = '(v)';
			$on = '(*)';
			$off = '( )';
		}

		foreach ($this->tableColumns as $name => $label) {
			$finalLabel = $label;
			if($this->sortable && !in_array($name, $this->dontSort)) {
				$sortUrl = clone($url);
				$sortUrl->browseBy = $name;

				if(isset($query['browseBy']) && $query['browseBy'] == $name) {
					if(isset($query['order']) && $query['order'] == 'desc') {
						$sortUrl->order = 'asc';
						$finalLabel = $down . ' ' . $finalLabel;
					} else {
						$sortUrl->order = 'desc';
						$finalLabel = $up . ' ' . $finalLabel;
					}
				}

				$finalLabel = $sortUrl->getLink($finalLabel);
			}

			if($this->filterable && !in_array($name, $this->dontFilter)) {
				if(isset($query['filter'][$name])) {
					$dot = $on;
				} else {
					$dot = $off;
				}

				$div = new HtmlObject('div');
				$div->addClass('filter-menu');
				$div->wrapAround($finalLabel);
				$div->wrapAround($dot);
				$div->wrapAround($this->getFilterList($name));
				$finalLabel = (string) $div;
			}

			$table->addColumnLabel('model_' . $name, $finalLabel);
		}

		if($this->listActions)
			$table->addColumnLabel('model_actions', 'Actions');
	}

	protected function getFilterList($name)
	{
		if(!isset($this->filterValues[$name]))
			return '';

		if(in_array($name, $this->dontFilter))
			return '';

		if($iconset = $this->theme->getIconset()) {
			$on = $iconset->getIcon('dotblack', 'filter-dot-on', '(*)');
			$off = $iconset->getIcon('dotwhite', 'filter-dot-off', '( )');
		} else {
			$on = '(*)';
			$off = '( )';
		}

		$url = Query::getUrl();
		$query = Query::getQuery();

		$filter = isset($query['filter']) ? (array) $query['filter'] : array();
		$filterThis = isset($filter[$name]) ? (array) $filter[$name] : array();

		$list = array();
		$ul = new HtmlObject('ul');
		$ul->addClass('table-filter-list');

		$filterValues = $this->filterValues[$name];
		natcasesort($filterValues);
		foreach($filterValues as $value) {
			$li = new HtmlObject('li');
			$li->addClass('table-filter-list-item');

			$urlF = clone($url);

			$filterLocal = $filter;
			$filterThisLocal = $filterThis;

			if(!in_array($value, $filterThisLocal)) {
				$filterThisLocal[] = $value;
				$dot = $off;
			} else {
				$tempList = array();
				foreach($filterThisLocal as $ftl) {
					if($ftl != $value) {
						$tempList[] = $ftl;
					}
				}
				$filterThisLocal = $tempList;
				$dot = $on;
			}

			$filterLocal[$name] = $filterThisLocal;
			$urlF->filter = $filterLocal;
			$link = $urlF->getLink($dot . ' ' . $value);
			$li->wrapAround($link);
			$ul->wrapAround($li);
		}

		return $ul;
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
