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
 * This class acts as the default 'read' action for any model. It is ridiculous simple, as all the heavy lifting is done
 * by the ModelActionBase class.
 *
 * @package System
 * @subpackage ModelSupport
 */
class ModelActionIndex extends ModelActionBase
{
        public $adminSettings = array( 'headerTitle' => 'Index', 'listType' => 'table', 'paginate' => true );
        public $htmlSettings = array( 'headerTitle' => 'Index', 'listType' => 'table', 'paginate' => true );

	protected $listingClass = 'ModelListing';

	/**
	 * This is the date format used when converting the model to an html table.
	 *
	 * @var string
	 */
	protected $indexDateFormat = 'm.d.y g:i a';

	protected $count;
	protected $page;
	protected $size;
	protected $offset;

	protected $modelListing;

	public $indexBrowseBy = 'name';

	/**
	 * This is the maximum number of models a user can request at one time.
	 *
	 * @var int
	 */
	public $indexMaxLimit = 100;

	/**
	 * This is the default number of models returned if the user does not specify how many they want.
	 *
	 * @var int
	 */
	public $defaultSize = 20;

	/**
	 * This array contains the models requested by the user.
	 *
	 * @var array
	 */
	public $childModels = array();

	public function logic()
	{
		$this->loadOffsets();
		$modelInformationArray = $this->getChildren(array());
		$childrenModels = array();
		if(is_array($modelInformationArray))
		{
			foreach($modelInformationArray as $modelInfo)
			{
				$childModel = ModelRegistry::loadModel($this->model->getType(), $modelInfo['id']);
				$childrenModels[] = $childModel;
			}
		}

		$this->childModels = $childrenModels;
	}

	protected function loadOffsets()
	{
		$query = Query::getQuery();

		$this->modelListing = $this->getModelListingClass();
		$this->count = $this->modelListing->getCount();

		$this->size = isset($query['limit']) && is_numeric($query['limit'])
							? $query['limit']
							: $this->defaultSize;

		if($this->size > $this->indexMaxLimit)
			$this->size = $this->indexMaxLimit;

		if(isset($query['page']) && is_numeric($query['page']) && $query['page'] > 0) {
			$this->offset = $this->size * ($query['page'] - 1);
			$this->page = $query['page'];
		} elseif(isset($query['start'])) {
			$this->offset = (int) $query['start'];
			$this->page = 0;
		} else {
			$this->offset = 0;
			$this->page = 1;
		}
	}

	/**
	 * This function ties the user input into a Listing class retrieved from getModelListingClass() and returns the
	 * models to the logic function.
	 *
	 * @param array $restrictions
	 * @return array Contains keys 'type' and 'id'
	 */
	protected function getChildren($restrictions)
	{
		foreach($restrictions as $restrictionName => $restrictionValue)
			$this->modelListing->addRestriction($restrictionName, $restrictionValue);

		$listing = $this->modelListing->getListing($this->size, $this->offset);
		return $listing;
	}

	/**
	 * This function initiates and sets up the Listing class used by the getChildren class. When overloading this class
	 * this function is an ideal starting place.
	 *
	 * @return LocationListing
	 */
	protected function getModelListingClass()
	{
		if(!($tables = $this->model->getTables()))
			throw new CoreError('Models using the core ModelListingClass need to have a base table defined.');

		$listingClass = $this->listingClass;
		$listingObject = new $listingClass($tables[0], $this->model->getType());
		return $listingObject;
	}

	protected function getDisplayList($type = 'table')
	{
		if($type == 'template') {
			$class = 'ViewTemplateDisplayList';
		} else {
			$class = 'ViewTableDisplayList';
		}

		$indexList = new $class($this->model, $this->childModels);

		if($type == 'table') {
			$indexList->useIndex(true, $this->offset);
		}

		return $indexList;
	}

	protected function getPagination()
	{
		if(!isset($this->page))
			return '';
		
		$p = new TagBoxPagination($this->model);
		$url = Query::getUrl();
		$p->defineListing($this->count, $this->size, $this->page, $url, 
			$this->offset + 1, $this->offset + count($this->childModels));

		if($this->page === 0)
			$p->setOnPage(false);

		return $p->pageList();
	}

	/**
	 * Creates a listing of models along with relevant qualities and actions for use in an admin page.
	 *
	 * @return string
	 */
	public function viewAdmin($page)
	{
		$pagination = (isset($this->adminSettings['paginate']) && $this->adminSettings['paginate'])
			? $this->getPagination()
			: '';

		$indexList = $this->getDisplayList($this->adminSettings['listType']);
		$indexList->addPage($page);

		return $pagination . $indexList->getListing(). $pagination;
	}

	public function viewHtml($page)
	{
		$pagination = (isset($this->htmlSettings['paginate']) && $this->adminSettings['paginate'])
			? $this->getPagination()
			: '';
		$indexList = $this->getDisplayList($this->htmlSettings['listType']);
		$indexList->addPage($page);

		$data = $pagination . $indexList->getListing() . $pagination;

		if($this->htmlSettings['listType'] == 'template') {
			return $this->modelToHtml($page, $this->model, 'Display.html', array('listing' => $data));
		} else {
			return $data;
		}
	}

	public function viewXml()
	{

	}

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