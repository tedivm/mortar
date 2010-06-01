<?php

class ModelToHtmlList extends ModelToHtml
{

	protected $listingClass = 'ModelListing';
	protected $tableDisplayList = 'ViewTableDisplayList';
	protected $templateDisplayList = 'ViewTemplateDisplayList';

	/**
	 * This is the date format used when converting the model to an html table.
	 *
	 * @var string
	 */
	protected $indexDateFormat = 'm.d.y g:i a';

	protected $options = array();

	protected $listType = 'template';
	protected $recursive = false;
	protected $paginate = false;

	protected $count;
	protected $page;
	protected $size;
	protected $offset;
	protected $columns;
	protected $restrictions = array();

	protected $configuration;
	protected $modelListing;
	protected $processed = false;

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


	public function __construct(Model $model, $template = null, $recursive = false, $options = array())
	{
		parent::__construct($model, $template);
		$this->options = $options;
		$this->recursive = $recursive;
	}

	protected function process()
	{
		if(!$this->recursive) {
			$this->options = $options;
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
	 * @return array Contains keys 'type' and 'id'
	 */
	protected function getChildren()
	{
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

		foreach($this->restrictions as $restrictionName => $restrictionValue)
			$listingObject->addRestriction($restrictionName, $restrictionValue);

		foreach($this->options as $optionName => $optionValue)
			$listingObject->setOption($optionName, $optionValue);

		return $listingObject;
	}

	protected function getDisplayList($type = 'table')
	{
		if($type == 'template') {
			$class = $this->templateDisplayList;
		} else {
			$class = $this->tableDisplayList;
		}

		$indexList = new $class($this->model, $this->childModels);

		if($type == 'table')
			$indexList->useIndex(true, $this->offset);

		if(isset($this->columns))
			$indexList->useColumns($this->columns);

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

	public function addRestriction($name, $value)
	{
		$this->restrictions[$name] = $value;
	}

	public function getChildrenList()
	{
		if(!$this->processed)
			$this->process();
		return $this->childModels;
	}

	public function setColumns($columns)
	{
		$this->columns = $columns;
	}

	public function setListType($listType)
	{
		$listType = strtolower($listType);
		if($listType === 'table' || $listType === 'template') {
			$this->listType = $listType;
			return true;
		} else {
			return false;
		}
	}

	public function paginate($set = true)
	{
		$this->paginate = $set;
	}

	public function getOutput()
	{
		if(!$this->processed)
			$this->process();

		if($this->recursive)
			return parent::getOutput();

		$pagination = ($this->paginate)
			? $this->getPagination()
			: '';

		$indexList = $this->getDisplayList($this->listType);
		$indexList->addPage(ActivePage::getInstance());

		$this->modelDisplay->addContent(
			array('listing' => $pagination . $indexList->getListing(). $pagination));

		return parent::getOutput();
	}
}

?>