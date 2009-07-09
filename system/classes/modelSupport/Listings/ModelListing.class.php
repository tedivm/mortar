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
	 * This is the maximum number of children that will be returned by the class.
	 *
	 * @var int
	 */
	protected $maxLimit = 100;

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
	 * This contains the name of the table being used to retrieve the models.
	 *
	 * @var string
	 */
	protected $table;

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

		$this->restrictions = $restrictions;
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
				$model = ModelRegistry::loadModel($modelInfo['type'], $modelInfo['id']);

				if($model->checkAuth('Read', $user))
					$filteredModels[] = $modelInfo;

			}catch(Exception $e){

			}
		}
		return $filteredModels;
	}

	/**
	 * This function returns the specified number of models that meet all of the set requirements.
	 *
	 * @param int $number
	 * @param int $offset
	 * @return array
	 */
	public function getListing($number, $offset = 0)
	{
		if($number > $this->maxLimit)
			$number = $this->maxLimit;

		$processedModels = array();

		// We want to get the requested amount if possible, so if we do not have that many we will load the next batch
		// and add those to our array. To take advantage of caching we will load all of the next batch, not just the
		// amount of models we are missing.
		while($unprocessedModels = $this->getModels($number, $offset))
		{
			$processedModels = array_merge($processedModels, $this->filterModels($unprocessedModels));
			$modelCount = count($processedModels);
			if($modelCount >= $number)
			{
				if($modelCount > $number);
					$processedModels = array_slice($processedModels, 0, $number, true);

				break;
			}
			$offset = $offset + $number;
		}

		return (count($processedModels) > 0) ? $processedModels : false;
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

	/**
	 * This function is used internally to load a batch (number + offset) of models. It processed the cache, options,
	 * and restrictions while acting as a wrapper around the getModelsFromTable function.
	 *
	 * @cache This cache is dynamically keyed, see getCacheArray()
	 * @param int $offset
	 * @param int $number
	 * @return array
	 */
	protected function getModels($number, $offset)
	{
		$order = (isset($this->options['order']) && strtolower($this->options['order']) == 'desc') ? 'DESC' : 'ASC';
		$browseBy = isset($this->options['browseBy']) ? $this->options['browseBy'] : null;


		if(!($cacheKey = $this->getCacheArray()))
			return false;

		if($restrictionString = $this->getRestrictionString($this->restrictions))
			$cacheKey[] = $restrictionString;

		// we put order before offset because anything in descending isn't likely to change as often
		$cacheKey[] = 'browseChildrenBy';
		$cacheKey[] = $browseBy . '_' . $order;
		$cacheKey[] = $offset . '_' . $number;

		$cache = new Cache($cacheKey);
		$modelList = $cache->getData();

		if($cache->isStale() || true)
		{
			$modelList = $this->getModelsFromTable($this->table, $this->restrictions, $browseBy,
															$order, $number, $offset);
			$cache->storeData($modelList);
		}
		return $modelList;
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
	 * @return array
	 */
	protected function getModelsFromTable($table, $restrictions, $browseBy, $order, $number, $offset)
	{
		$orm = new ObjectRelationshipMapper($table);
		$orm->setColumnLimits(array_keys($this->lookupColumns));

		foreach($restrictions as $restrictionName => $restrictionValue)
			$orm->$restrictionName = $restrictionValue;

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
		if(!isset($this->restrictions['type']))
			return false;

		$cacheKey = array('models', $this->restrictions['type'], 'browseModelBy');
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

}

?>