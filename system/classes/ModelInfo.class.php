<?php

class ModelInfo
{
	static protected $handlerList;

	static public function getHandler($name)
	{
		if(!is_array(self::$handlerList))
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
					$handlers[$row['resource']] = array('name' => $row['name'], 'package' => $row['package']);
				}

				$cache->storeData($handlers);
			}
			self::$handlerList = $handlers;
		}

		return (self::$handlerList[$name]) ? self::$handlerList[$name] : false;
	}



}


?>