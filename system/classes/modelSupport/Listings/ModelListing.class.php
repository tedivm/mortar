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
 * This class is used to collect a list of related models for use in index and other classes.
 *
 * @package System
 * @subpackage ModelSupport
 */
class ModelListing
{
	/**
	 * This array contains options related to how the item is retrieved, such as how it is sorted. It is an associative
	 * array with each option being a key value pair.
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * This is the batch size in which models are loaded until enough models are able to be returned.
	 *
	 * @var int
	 */
	protected $batchSize = 200;

	/**
	 * This is the maximum number of children that will be returned by the class.
	 *
	 * @var int
	 */
	protected $maxLimit = 2000;

	/**
	 * This is how many batches have been loaded so far.
	 *
	 * @var int
	 */
	protected $batchesLoaded = 0;

	/**
	 * This array defines which columns map to what value (type or id). Because the ORM class is used the string
	 * "primarykey" will map to whatever the primary key of the table is.
	 *
	 * @var array
	 */
	protected $lookupColumns = array('primarykey' => 'id');

	/**
	 * This array contains all of the restrictions on what can be included in the list. It is an associative array with
	 * each key mapping to a column.
	 *
	 * @var array
	 */
	protected $restrictions = array();

	/**
	 * This array contains a list of function calls which need to be included in the WHERE clause of the select statement.
	 *
	 * @var array
	 */
	protected $functions = array();

	/**
	 * This contains the name of the table being used to retrieve the models.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The model type being listed by this class.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * The models loaded from the database for use by this class.
	 *
	 * @var string
	 */
	protected $models;

	/**
	 * Takes the name of the table and model type this instance will be listing.
	 *
	 * @param string $table
	 * @param string $type
	 */
	public function __construct($table, $type)
	{
		$this->table = $table;
		$this->type = $type;
	}

	/**
	 * This function is used to set or change the table that the class is mapped against.
	 *
	 * @param string $table
	 */
	public function setTable($table)
	{
		$this->table = $table;
	}

	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * This function sets an option (browseBy, order) for retrieving the models.
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function setOption($name, $value)
	{
		$this->options[$name] = $value;
	}

	/**
	 * This function overwrites the current restrictions array with the array passed.
	 *
	 * @param array $restrictions
	 */
	public function setRestrictions($restrictions)
	{
		if(!is_array($restrictions))
			throw new TypeMismatch(array('Array', $restrictions));

		foreach($restrictions as $name => $value)
			$this->addRestriction($name, $value);
	}

	/**
	 * This function sets a requirement for the models being retrieved. For this implementation the names are mapped
	 * directly to a database column, and the value sets what that column needs to be.
	 *
	 * @param string $name
	 * @param string|int $value
	 */
	public function addRestriction($name, $value)
	{
		$this->restrictions[$name] = $value;
	}

	/**
	 * This function adds a function/column pairing to the list which need to be included in the selection for
	 * this list.
	 *
	 * @param string $name
	 * @param string $function
	 * @param string|int $value
	 */
	public function addFunction($name, $function, $value)
	{
		$this->functions[] = array('name' => $name, 'function' => $function, 'value' => $value);
	}

	/**
	 * Returns a count of the number of models (up to the maxCount) present in the selected listing.
	 *
	 * @return int
	 */
	public function getCount()
	{
		$num = $this->getListing($this->maxLimit);
		return count($num);
	}

	public function filterBy($name, $content)
	{
		if(in_array($name, $this->lookupColumns))
			$name = array_search($name, $this->lookupColumns);

		$this->addRestriction($name, $content);
	}

	/**
	 * This function returns the specified number of models that meet all of the set requirements.
	 *
	 * @param int $number
	 * @param int $offset
	 * @return array|false
	 */
	public function getListing($number, $offset = 0)
	{
		if($number > $this->maxLimit)
			$number = $this->maxLimit;

		if($offset < 0)
			$offset = 0;

		$models = $this->getModels($number, $offset);

		return (count($models) > 0) ? $models : false;
	}

	/**
	 * Loads models in set batches (for caching purposes) from the database, checking after each
	 * batch whether enough models are present, then returning either the requested number or
	 * as many as possible if not enough models are present.
	 *
	 * @param int $number
	 * @param int $offset
	 * @return array
	 */
	protected function getModels($number, $offset)
	{
		$batch = 0;
		$allModels = array();
		while($this->loadModels($batch) === true) {
			$allModels = array_merge($allModels, $this->filterModels($this->models[$batch]));

			if(count($allModels) >= $number + $offset) {
				return array_slice($allModels, $offset, $number);
			}

			$batch++;
		}

		return array_slice($allModels, $offset);
	}

	/**
	 * This function filters the retrieved models by permission, testing against the active user.
	 *
	 * @param array $modelArray
	 * @return array
	 */
	protected function filterModels($modelArray)
	{
		$user = ActiveUser::getUser();
		$filteredModels = array();
		foreach($modelArray as $modelInfo)
		{
			try
			{
				if(!isset($modelInfo['type']))
					$modelInfo['type'] = $this->type;
				$model = ModelRegistry::loadModel($modelInfo['type'], $modelInfo['id']);

				if($model->checkAuth('Read', $user))
					$filteredModels[] = $modelInfo;

			}catch(Exception $e){

			}
		}
		return $filteredModels;
	}

	/**
	 * This function is used internally to load a batch of models. It processed the cache, options,
	 * and restrictions while acting as a wrapper around the getModelsFromTable function. Each batch
	 * is a set number of models starting from the first result -- i.e. batch 0 is 
	 * models 0 through (batchSize - 1), batch 1 is models (batchSize) through (batchSize * 2) - 1,
	 * etc.
	 *
	 * @cache This cache is dynamically keyed, see getCacheArray()
	 * @param int $batch
	 * @return array Contains an array of associative arrays with index type and id
	 */
	protected function loadModels($batch)
	{
		if(isset($this->models[$batch]) && $this->models[$batch])
			return true;

		$order = (isset($this->options['order']) && strtolower($this->options['order']) == 'desc') ? 'DESC' : 'ASC';
		if(isset($this->options['browseBy'])) {
			if(in_array($this->options['browseBy'], $this->lookupColumns)) {
				$browseBy = array_search($this->options['browseBy'], $this->lookupColumns);
			} else {
				$browseBy = $this->options['browseBy'];
			}
		} else {
			$browseBy = null;
		}

		if(!($cacheKey = $this->getCacheArray()))
			return false;

		if($restrictionString = $this->getRestrictionString($this->restrictions))
			$cacheKey[] = $restrictionString;

		if($functionString = $this->getFunctionString($this->functions))
			$cacheKey[] = $functionString;

		$cacheKey[] = 'browseChildrenBy';
		$cacheKey[] = $browseBy . '_' . $order;
		$cacheKey[] = 'batch_' . $batch;

		$cache = CacheControl::getCache($cacheKey);
		$modelList = $cache->getData();

		if($cache->isStale())
		{
			$modelList = $this->getModelsFromTable($this->table, $this->restrictions, $this->functions, $browseBy,
								$order, $this->batchSize, $this->batchSize * $batch);
			$cache->storeData($modelList);
		}

		$this->models[$batch] = $modelList;

		return ($modelList === false) ? false : true;
	}

	/**
	 * This function is what actually retrieves the list of models from the database. It uses the ORM class and is
	 * only called when the cache misses.
	 *
	 * @param string $table
	 * @param string $restrictions
	 * @param string $browseBy
	 * @param string $order
	 * @param int $number
	 * @param int $offset
	 * @return array Contains an array of associative arrays with index type and id
	 */
	protected function getModelsFromTable($table, $restrictions, $functions, $browseBy, $order, $number, $offset)
	{
		$orm = new ObjectRelationshipMapper($table);
		$orm->setColumnLimits(array_keys($this->lookupColumns));

		foreach($restrictions as $restrictionName => $restrictionValue)
			$orm->$restrictionName = $restrictionValue;

		foreach($functions as $func)
			$orm->addFunction($func['name'], $func['function'], $func['value']);

		$orm->select($number, $offset, $browseBy, $order);

		if($orm->totalRows() > 0)
		{
			$modelList = array();
			do{
				$modelInfo = array();
				foreach($this->lookupColumns as $cName => $attributeName)
					$modelInfo[$attributeName] = $orm->$cName;

				$modelList[] = $modelInfo;
			}while($orm->next());
		}else{
			$modelList = false;
		}
		return $modelList;
	}

	/**
	 * This function returns the base array used to distinguish this cache item from others. This is just a base- the
	 * options and restrictions get added seperately.
	 *
	 * @return array
	 */
	protected function getCacheArray()
	{
		$cacheKey = array('models', $this->type, 'browseModelBy');
		return $cacheKey;
	}

	/**
	 * This function takes in the restriction array and returns a string.
	 *
	 * @param array $restrictions
	 * @return string
	 */
	protected function getRestrictionString($restrictions)
	{
		$restrictionString = '';
		foreach($restrictions as $rName => $rValue)
			$restrictionString .= $rName . ':' . $rValue . '::';

		return ($restrictionString != '') ? $restrictionString : false;
	}

	/**
	 * This function takes in the function array and returns a string.
	 *
	 * @param array $functions
	 * @return string
	 */
	protected function getFunctionString($functions)
	{
		$functionString = '';
		foreach($functions as $val)
			$functionString .= $val['name'] . ':' . $val['function'] . ':' . $val['value'] . '::';

		return ($functionString != '') ? $functionString : false;
	}
}

?>