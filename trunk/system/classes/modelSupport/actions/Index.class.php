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

        public $adminSettings = array( 'headerTitle' => 'Index' );
        public $htmlSettings = array( 'headerTitle' => 'Index' );

	protected $listingClass = 'ModelListing';

	protected $indexDateFormat = 'm.d.y g:i a';

	public $indexBrowseBy = 'name';

	public $indexMaxLimit = 100;

	public $indexLimit = 10;

	public $childModels = array();

	public function logic()
	{
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

	protected function getChildren($restrictions)
	{
		$query = Query::getQuery();

		$offset = isset($query['start']) ? $query['start'] : 0;
		$numberChildren = isset($query['limit']) && is_numeric($query['limit'])
							? $query['limit']
							: $this->indexLimit;

		if($numberChildren > $this->indexMaxLimit)
			$numberChildren = $this->indexMaxLimit;

		$modelListing = $this->getModelListingClass();

		foreach($restrictions as $restrictionName => $restrictionValue)
			$modelListing->addRestriction($restrictionName, $restrictionValue);

		$listing = $modelListing->getListing($numberChildren, $offset);
		return $listing;
	}

	protected function getModelListingClass()
	{
		if(!($tables = $this->model->getTables()))
			throw new CoreError('Models using the core ModelListingClass need to have a base table defined.');

		$listingClass = $this->listingClass;
		$listingObject = new $listingClass($tables[0], $this->model->getType());
		return $listingObject;
	}


	protected function getTableDisplayList()
	{
		$indexList = new ViewTableDisplayList($this->model, $this->childModels);
		return $indexList;
	}

	protected function getTemplateDisplayList()
	{
		$readList = new ViewTemplateDisplayList($this->model, $this->childModels);
		return $readList;
	}

	public function viewAdmin($page)
	{
		$this->setTitle($this->adminSettings['headerTitle']);

		$indexList = $this->getTableDisplayList();
		$indexList->addPage($page);

		return $indexList->getListing();
	}

	public function viewHtml($page)
	{
		$this->setTitle($this->htmlSettings['headerTitle']);

		$output = parent::viewHtml($page);
		$readList = $this->getTemplateDisplayList();
		$readList->addPage($page);

		if($listingResults = $readList->getListing())
			$output .= $listingResults;

		return $output;
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