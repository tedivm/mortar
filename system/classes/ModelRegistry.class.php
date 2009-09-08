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
	 * This clears the handlers
	 *
	 * @static
	 */
	static public function clearHandlers()
	{
		self::$handlerList = null;
	}

	/**
	 * Returns a handler for the specified resource type, or false if one doesn't exist
	 *
	 * @static
	 * @param string $name
	 * @return array|bool
	 */
	static public function getHandler($name)
	{
		if(!is_scalar($name))
			throw new TypeMismatch(array('String', $name));

		if(!is_array(self::$handlerList))
			self::loadHandlers();

		return (isset(self::$handlerList[$name])) ? self::$handlerList[$name] : false;
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

		$db = db_connect('default');
		$insertStmt = $db->stmt_init();
		$insertStmt->prepare('REPLACE INTO modelsRegistered (handlerName, resource, mod_id) VALUES (?, ?, ?)');
		$insertStmt->bindAndExecute('ssi', $name, $resource, $moduleInfo->getId());

		Cache::clear('system', 'models', 'handlers');
		self::loadHandlers();
	}

	/**
	 * Returns a list of models that are registered by the system
	 *
	 * @static
	 * @return array
	 */
	static public function getModelList()
	{
		if(!is_array(self::$handlerList))
			self::loadHandlers();

		return array_keys(self::$handlerList);
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
			return $model;

		}catch(Exception $e){
			return false;
		}
	}

	/**
	 * Loads the handlers from the database or cache
	 *
	 * @access protected
	 * @cache system models handlers
	 */
	static protected function loadHandlers()
	{
		$cache = new Cache('system', 'models', 'handlers');
		$handlers = $cache->getData();

		if($cache->isStale())
		{
			$handlers = array();
			$db = dbConnect('default_read_only');
			$moduleRows = $db->query('SELECT * FROM modelsRegistered');

			while($row = $moduleRows->fetch_assoc())
			{
				$moduleInfo = new PackageInfo($row['mod_id']);

				$className = $moduleInfo->getName() . 'Model' . $row['handlerName'];

				$handlers[$row['resource']] = array('id' => $row['modelId'],
													'name' => $row['handlerName'],
													'module' => $row['mod_id'],
													'resource' => $row['resource'],
													'class' => $className);
			}

			$cache->storeData($handlers);
		}
		self::$handlerList = $handlers;
	}

}

class ModuleRegistryError extends CoreError {}
?>