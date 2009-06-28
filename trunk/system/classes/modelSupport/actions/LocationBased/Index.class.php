<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage ModelSupport
 */

/**
 * This class acts as the default 'read' action for any model. It is ridiculous simple, as all the heavy lifting is done
 * by the ModelActionBase class.
 *
 * @package System
 * @subpackage ModelSupport
 */
class ModelActionLocationBasedIndex extends ModelActionLocationBasedRead
{

	public $indexBrowseBy = 'creationDate';
	public $indexBrowseOptions = array('name', 'resourceType', 'creationDate', 'owner', 'groupOwner', 'lastModified');
	public $indexMaxLimit = 100;
	public $indexLimit = 10;
	public $childModels = array();

	/**
	 * This literally does nothing at all.
	 *
	 */
	public function logic()
	{
		$childLocations = $this->getChildren();
		$childrenModels = array();
		foreach($childLocations as $childId)
		{
			$location = new Location($childId);
			$childrenModels[] = $location->getResource();
		}

		$this->childModels = $childrenModels;
	}

	protected function getChildren()
	{
		$query = Query::getQuery();
		$location = $this->model->getLocation();

		$offset = isset($query['start']) ? $query['start'] : 0;
		$numberChildren = isset($query['limit']) && is_numeric($query['limit'])
							? $query['limit']
							: $this->indexLimit;

		if($numberChildren > $this->indexMaxLimit)
			$numberChildren = $this->indexMaxLimit;

		if(isset($query['browseby']) && $key = array_search($query['browseby'], $this->indexBrowseOptions))
		{
			$browseBy = $this->indexBrowseOptions[$key];
		}else{

			if(isset($query['month']) && is_numeric($query['month']) && $query['month'] > 0 & $query['month'] <= 12)
			{
				$browseBy = 'month';
			}else{
				$browseBy = $this->indexBrowseBy;
			}
			$browseBy = $this->indexBrowseBy;
		}


		$user = ActiveUser::getUser();
		$processedIds = array();

		switch($browseBy)
		{
			case 'month':
				while($childIds = $this->getChildrenByMonth($offset, $numberChildren))
				{
					$processedIds = array_merge($processedIds, $this->filterChildren($childIds));
					if($processedIds >= $numberChildren)
					{
						if($processedIds > $numberChildren);
							$processedIds = array_slice($processedIds, 0, $numberChildren, true);

						break;
					}
					$offset = $offset + $numberChildren;
				}
				break;

			default:
				while($childIds = $this->getChildrenByBrowsing($offset, $numberChildren, $browseBy))
				{
					$processedIds = array_merge($processedIds, $this->filterChildren($childIds));
					if($processedIds >= $numberChildren)
					{
						if($processedIds > $numberChildren);
							$processedIds = array_slice($processedIds, 0, $numberChildren, true);

						break;
					}
					$offset = $offset + $numberChildren;
				}
				break;
		}

		return $processedIds;
	}

	protected function filterChildren($childIds)
	{
		$user = ActiveUser::getUser();
		$processedIds = array();
		foreach($childIds as $id)
		{
			$permission = new Permissions($id, $user);
			if($permission->isAllowed('Read'))
				$processedIds[] = $id;
		}
		return $processedIds;
	}

	protected function getChildrenByBrowsing($offset, $numberChildren, $browseBy = 'creationDate')
	{
		$query = Query::getQuery();

		$locationId = $this->model->getLocation()->getId();
		$cache = new Cache('locations', $locationId, 'children', 'browseBy', $browseBy, $offset, $numberChildren);
		$childrenLocations = $cache->getData();

		if($cache->isStale())
		{
			$selectStmt = DatabaseConnection::getStatement();
		  	$selectStmt->prepare('SELECT location_id
							  		FROM locations
							  		WHERE parent = ?
							  		ORDER BY ?
							  		LIMIT ?, ?');
			$selectStmt->bindAndExecute('isii', $locationId, $browseBy, $offset, $numberChildren);

			if($selectStmt->num_rows() > 0)
			{
				$childrenLocations = array();
				while($row = $selectStmt->fetch_array())
					$childrenLocations[] = $row['location_id'];

			}else{
				$childrenLocations = false;
			}

			$cache->storeData($childrenLocations);
		}
		return $childrenLocations;
	}

	protected function getChildrenByMonth($offset, $numberChildren)
	{
		$query = Query::getQuery();
		$month = (isset($query['month']) && is_numeric($query['month'])) ? $query['month'] : gmdate('m');
		$year = (isset($query['year']) && is_numeric($query['year'])) ? $query['year'] : gmdate('Y');

		$locationId = $this->model->getLocation()->getId();
		$cache = new Cache('locations', $locationId, 'children', 'browseByTime', $year, $month, $offset, $numberChildren);
		$childrenLocations = $cache->getData();

		if($cache->isStale())
		{
			if($month !== date('m') || $year !== date('Y'))
				$cache->cacheTime = 86400;

			$startTime = date('Y-m-d H:i:s', mktime(0, 0, 0, $month, 1, $year));

			if($month == 12)
			{
				$endTime = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, $year + 1));
			}else{
				$endTime = date('Y-m-d H:i:s', mktime(0, 0, 0, $month + 1, 1, $year));
			}
			$selectStmt = DatabaseConnection::getStatement();
			$selectStmt->prepare('SELECT location_id
							  		FROM locations
							  		WHERE parent = ?
							  			AND	(creationDate >= ? AND creationDate < ?)
							  		LIMIT ?, ?');
			$selectStmt->bindAndExecute('issii', $locationId, $startTime, $endTime, $offset, $numberChildren);

			if($selectStmt->num_rows() > 0)
			{
				$childrenLocations = array();
				while($row = $selectStmt->fetch_array())
					$childrenLocations[] = $row['location_id'];

			}else{
				$childrenLocations = false;
			}

			$cache->storeData($childrenLocations);
		}

		return $childrenLocations;
	}





	/**
	 * This is incredibly basic right now, but thats because I'm working woth the Joshes on getting the interface
	 * for it set up.
	 *
	 * @return string
	 */
	public function viewAdmin($page)
	{
		$menu = $page->getMenu('actions', 'modelNav');
		$this->makeModelActionMenu($menu, $this->model);

		$table = new Table('test');
		$table->addClass('model-listing');
		$table->enableIndex();

		foreach($this->childModels as $model)
			$this->addModelToTable($table, $model);

		return $table->makeHtml();
	}


	/**
	 * This function adds a model class to a Table
	 *
	 * @param table $table
	 * @param Model $model
	 */
	protected function addModelToTable($table, $model)
	{
		$table->newRow();
		$table->addField('name', 'value');

		$baseUrl = new Url();
		if(method_exists($model, 'getLocation'))
		{
			$location = $model->getLocation();
			$baseUrl->locationId = $location->getId();
			$table->addField('name', $location->getName());
		}else{
			$baseUrl->type = $model->getType();
			$baseUrl->id = $model->getId();
		}

		$baseUrl->format = 'Admin';

		$table->addField('read', $baseUrl->getLink('Read'));


		$actionTypes = array('Index', 'Edit', 'Delete');

		$user = ActiveUser::getUser();
		$userId = $user->getId();
		foreach($actionTypes as $action)
		{
			$actionUrl = clone $baseUrl;
			$actionUrl->action = $action;

			if($actionUrl->checkPermission($userId))
				$table->addField($action, $actionUrl->getLink(ucfirst($action)));
		}


	}







	/**
	 * This function takes the model's data and puts it into a template, which gets injected into the active page. It
	 * also takes out some model data to place in the rest of the template (title, keywords, descriptions).
	 *
	 * @return string This is the html that will get injected into the template.
	 */
	public function viewHtml($page)
	{
		$output = parent::viewHtml($page);
		$output .= $this->childrenToHtml($page, 'Listing.html');
		return $output;
	}

	protected function childrenToHtml($page, $templateName)
	{
		if(count($this->childModels) > 0)
		{
			$listingHtml = new HtmlObject('div');
			$listingHtml->property('name', 'listing-container');

			$templates = array();
			$x = 1;
			foreach($this->childModels as $model)
			{
				if($modelDisplay = $this->modelToHtml($page, $model, $templateName))
				{
					$listingHtml->insertNewHtmlObject('div')->
						property('name', 'listing-container-child-' . $x)->
						addClass('modelListing')->addClass($model->getType())->
						wrapAround($modelDisplay);
					$x++;
				}
			}

			$output = (string) $listingHtml;
		}

		return $output;
	}




	/**
	 * This will convert the model into XML for outputting.
	 *
	 * @return string XML
	 */
	public function viewXml()
	{
		if(count($this->childModels) > 0)
		{

		}else{

		}
	}

	/**
	 * This takes the model and turns it into an array. The output controller converts that to json, which gets
	 * outputted.
	 *
	 * @return array
	 */
	public function viewJson()
	{
		$children = array();
		if(count($this->childModels) > 0)
		{
			foreach($this->childModels as $model)
			{
				$children[] = $model->__toArray();
			}
			return $children;
		}else{
			return false;
		}
	}
}

?>