<?php

class ModelRegistry
{
	protected static $handlerList;

	static public function clearHandlers()
	{
		self::$handlerList = null;
	}

	static public function getHandler($name)
	{
		if(!is_scalar($name))
			throw new TypeMismatch(array('String', $name));

		if(!is_array(self::$handlerList))
			self::loadHandlers();

		return (self::$handlerList[$name]) ? self::$handlerList[$name] : false;
	}

	static public function setHandler($resource, $module, $name = null)
	{
		$moduleInfo = new PackageInfo($module);

		if(is_null($name))
			$name = $resource;

		$db = db_connect('default');
		$insertStmt = $db->stmt_init();
		$insertStmt->prepare('REPLACE INTO modelsRegistered (name, resource, mod_id) VALUES (?, ?, ?)');
		$insertStmt->bind_param_and_execute('ssi', $name, $resource, $moduleInfo->getId());

		Cache::clear('system', 'models', 'handlers');
		self::loadHandlers();
	}

	static public function getModelList()
	{
		if(!is_array(self::$handlerList))
			self::loadHandlers();

		return array_keys(self::$handlerList);
	}

	static public function loadModel($type, $id = null)
	{
		$modelInfo = self::getHandler($type);

		if(!$modelInfo)
			throw new BentoError('Unable to load handler for model ' . $type . '.');

		$handler = importFromModule($modelInfo['name'], $modelInfo['module'], 'Model', true);

		$model = new $handler($id);
		return $model;
	}

	static protected function loadHandlers()
	{
		$cache = new Cache('system', 'models', 'handlers');
		$handlers = $cache->getData();

		if(!$cache->cacheReturned)
		{
			$handlers = array();
			$db = dbConnect('default_read_only');
			$moduleRows = $db->query('SELECT * FROM modelsRegistered');

			while($row = $moduleRows->fetch_assoc())
			{
				$moduleInfo = new PackageInfo($row['mod_id']);

				$className = $moduleInfo->getName() . 'Model' . $row['name'];

				$handlers[$row['resource']] = array('name' => $row['name'],
													'module' => $row['mod_id'],
													'resource' => $row['resource'],
													'class' => $className);
			}

			$cache->storeData($handlers);
		}
		self::$handlerList = $handlers;
	}

}

?>