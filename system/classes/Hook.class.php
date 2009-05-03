<?php

class Hook
{
	protected $realm;
	protected $category;
	protected $name;
	protected $plugins;

	protected $interfaces = array();

	public function __construct($realm, $category, $name)
	{
		if(!isset($realm))
			throw new TypeMismatch(array('String or Location', $realm));

		if(!isset($category))
			throw new TypeMismatch(array('String', $category));

		if(!isset($name))
			throw new TypeMismatch(array('String', $name));

		$this->realm = $realm;
		$this->category = $category;
		$this->name = $name;
	}

	public function enforceInterface($interface)
	{
		if(!class_exists($interface, false))
			throw new BentoError('Attempting to add nonexistent interface ' . $interface . ' to plugin');

		$this->interfaces[] = $interface;
	}

	public function getPlugins()
	{
		if(!$this->plugins)
			$this->load();

		return $this->plugins;
	}

	protected function load()
	{
		$realm = ($this->realm instanceof Location) ? 'Resource' . $this->realm->getType() : $this->realm;

		$cache = new Cache('plugins', $realm, $this->category, $this->name, 'list');
		$pluginList = $cache->getData();

		if(!$cache->cacheReturned)
		{
			$pluginList = array();
			$db = DatabaseConnection::getConnection('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare('SELECT module, plugin FROM plugins WHERE realm = ? AND category = ? AND name = ?');

			if($stmt->bindAndExecute('sss', $realm, $this->category, $this->name))
			{
				while($row = $stmt->fetch_array())
				{
					$className = importFromModule($row['plugin'], $row['module'], 'plugin');

					$classReflection = new ReflectionClass($className);
					foreach($this->interfaces as $interface)
					{
						if(!$classReflection->implementsInterface($interface))
							continue 2;
					}

					$pluginList[] = $row;
				}
			}else{
				$pluginList = false;
			}
			$cache->storeData($pluginList);
		}

		$classList = array();
		foreach($pluginList as $plugin)
		{
			$classList[] = importFromModule($plugin['plugin'], $plugin['module'], 'plugin');
		}
		$pluginObjects = array();
		foreach($classList as $class)
		{
			try {
				$newPlugin = new $class();
				$pluginObjects[] = $newPlugin;
			}catch(Exception $e){}
		}
		$this->plugins = $pluginObjects;
	}

	public function __call($name, $arguments)
	{
		$responses = array();
		$plugins = $this->getPlugins();
		foreach($plugins as $plugin)
		{
			try{
				if(method_exists($plugin, $name))
				{
					$response= call_user_func_array(array($plugin, $name), $arguments);
					$responses[] = $response;
				}
			}catch(Exception $e){

			}
		}
		return $responses;
	}


	static public function registerPlugin($realm, $category, $name, $module, $plugin)
	{
		$db = DatabaseConnection::getConnection('default');
		$stmt = $db->stmt_init();
		$stmt->prepare('INSERT INTO plugins (realm, category, name, module, plugin) VALUES (?, ?, ?, ?, ?)');
		$stmt->bindAndExecute('sssis', $realm, $category, $name, $module, $plugin);
		Cache::clear('plugins', $realm, $category, $name);
	}

}


?>