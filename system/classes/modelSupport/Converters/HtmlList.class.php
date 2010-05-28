<?php

class ModelToHtmlList extends ModelToHtml
{

	protected $listingClass = 'ModelListing';
	protected $tableDisplayList = 'ViewTableDisplayList';
	protected $templateDisplayList = 'ViewTemplateDisplayList';

	protected $indexDateFormat = 'm.d.y g:i a';

	protected $listType = 'template';
	protected $recursive = false;
	protected $paginate = false;

	protected $count;
	protected $page;
	protected $size;
	protected $offset;

	protected $modelListing;

	public $indexBrowseBy = 'name';

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


	public function __construct(Model $model, $template = null, $recursive = false)
	{
		parent::__construct($model, $template);
		$this->configure($model, $template, $recursive);
	}
	
	protected function configure($model, $template, $recursive)
	{
		if(!$recursive) {
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
		} else {
			$this->recursive = true;
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
			$class = $this->templateDisplayList;
		} else {
			$class = $this->tableDisplayList;
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

	public function getChildrenList()
	{
		return $this->childModels;
	}

	public function paginate($set = true)
	{
		$this->paginate = $set;
	}

	public function getOutput()
	{
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