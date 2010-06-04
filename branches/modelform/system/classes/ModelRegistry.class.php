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
 * This class tracks the handlers for all of the models in the system
 *
 * @package System
 * @subpackage ModelSupport
 */
class ModelRegistry
{
	/**
	 * This is a master list of all the handlers
	 *
	 * @access protected
	 * @static
	 * @var array
	 */
	protected static $handlerList;

	/**
	 * This is used for when a string (representing a model type) is passed to the getHandler function. This maps the
	 * resource names to their ID.
	 *
	 * @var array
	 */
	protected static $resourceIndex;

	/**
	 * Stores models which have already been created in the course of this request so that they can be returned without
	 * additional database calls.
	 *
	 * @var array
	 */
	protected static $createdModels = array();

	/**
	 * This clears the handlers
	 *
	 * @static
	 */
	static public function clearHandlers()
	{
		self::$handlerList = null;
		self::$resourceIndex = null;
	}

	/**
	 * This function returns the ID of a model type.
	 *
	 * @param string $resource
	 * @return int
	 */
	static public function getIdFromType($resource)
	{
		if(isset(self::$resourceIndex[$resource]))
			return self::$resourceIndex[$resource];
		return false;
	}

	/**
	 * Returns a handler for the specified resource type, or false if one doesn't exist
	 *
	 * @static
	 * @param int|string $name
	 * @return array|bool
	 */
	static public function getHandler($id)
	{
		if(!is_scalar($id))
			throw new TypeMismatch(array('Integer or String', $id));

		if(!is_array(self::$handlerList))
			self::loadHandlers();

		if(!is_numeric($id) && isset(self::$resourceIndex[$id]))
			$id = self::$resourceIndex[$id];

		return (isset(self::$handlerList[$id])) ? self::$handlerList[$id] : false;
	}

	/**
	 * Sets the handler for the specified resource type
	 *
	 * @static
	 * @param string $resource resource type
	 * @param int $module This is the module that contains the new handler
	 * @param string $name name of the new handler
	 */
	static public function setHandler($resource, $module, $name = null)
	{
		$moduleInfo = new PackageInfo($module);

		if(is_null($name))
			$name = $resource;

		$moduleId = $moduleInfo->getId();

		$db = db_connect('default');
		$insertStmt = $db->stmt_init();
		$insertStmt->prepare('REPLACE INTO modelsRegistered (handlerName, resource, mod_id) VALUES (?, ?, ?)');
		$insertStmt->bindAndExecute('ssi', $name, $resource, $moduleId);

		$className = $moduleInfo->getName() . 'Model' . $name;
		$modelId = $insertStmt->insert_id;

		self::$resourceIndex[$resource] = $modelId;
		self::$handlerList[$modelId] = array('id' => $modelId,
											'name' => $name,
											'module' => $moduleId,
											'resource' => $resource,
											'class' => $className);
		CacheControl::clearCache('system', 'models', 'handlers');
	}

	/**
	 * Returns a list of models that are registered by the system
	 *
	 * @static
	 * @return array
	 */
	static public function getModelList()
	{
		if(!is_array(self::$resourceIndex))
			self::loadHandlers();

		$models = array_keys(self::$resourceIndex);
		sort($models, SORT_STRING);

		return $models;
	}

	/**
	 * Returns the resource that corresponds to the appropriate type and id or false if its unable to load.
	 *
	 * @param string $type
	 * @param int|null $id
	 * @return Model returns false on failure
	 */
	static public function loadModel($type, $id = null)
	{
		if(isset($id) && isset(self::$createdModels[$type][$id]))
			return self::$createdModels[$type][$id];

		try{
			$modelInfo = self::getHandler($type);

			if(!$modelInfo)
				throw new ModuleRegistryError('Unable to load handler for model ' . $type . '.');

			if(!class_exists($modelInfo['class'], false))
			{
				$handler = importFromModule($modelInfo['name'], $modelInfo['module'], 'Model', true);
			}else{
				$handler = $modelInfo['class'];
			}

			$model = new $handler($id);
			if(isset($id) && $model->getId() === false)
				return false;

			if(isset($id))
				self::$createdModels[$type][$id] = $model;

			return $model;

		}catch(Exception $e){
			return false;
		}
	}

	static public function clear()
	{
		self::$createdModels = array();
	}

	/**
	 * Loads the handlers from the database or cache
	 *
	 * @access protected
	 * @cache system models handlers
	 */
	static protected function loadHandlers()
	{
		$cache = CacheControl::getCache('system', 'models', 'handlers');
		$data = $cache->getData();

		if($cache->isStale())
		{
			$data = array();
			$handlers = array();
			$index = array();
			$db = dbConnect('default_read_only');
			$moduleRows = $db->query('SELECT * FROM modelsRegistered');

			while($row = $moduleRows->fetch_assoc())
			{
				$moduleInfo = new PackageInfo($row['mod_id']);

				$className = $moduleInfo->getName() . 'Model' . $row['handlerName'];

				$index[$row['resource']] = $row['modelId'];
				$handlers[$row['modelId']] = array('id' => $row['modelId'],
													'name' => $row['handlerName'],
													'module' => $row['mod_id'],
													'resource' => $row['resource'],
													'class' => $className);
			}

			$data['index'] = $index;
			$data['handlers'] = $handlers;
			$cache->storeData($data);
		}
		self::$resourceIndex = $data['index'];
		self::$handlerList = $data['handlers'];
	}

}

class ModuleRegistryError extends CoreError {}
?>