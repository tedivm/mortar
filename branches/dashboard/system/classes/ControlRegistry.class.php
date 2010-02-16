<?php

class ControlRegistry
{
	protected $controls;

	static public function registerControl($name, $format, $module, $class)
	{
		$db = DatabaseConnection::getConnection('default');
		$stmt = $db->stmt_init();
		$stmt->prepare('INSERT INTO controls (controlFormat, controlName, moduleId, controlClass)
				VALUES (?, ?, ?, ?)');
		$stmt->bindAndExecute('ssis', $name, $module, $class);
	}

	static public function getControl($format, $name)
	{
		if(defined('INSTALLMODE') && INSTALLMODE == true)
			return false;

		$cache = CacheControl::getCache('controls', $format, $name);

		$data = $cache->getData();
		if($cache->isStale()) {
			$data = array();
			$db = DatabaseConnection::getConnection('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare('SELECT controlName, moduleId, controlClass
					FROM plugins 
					WHERE controlFormat = ? AND controlName = ?');
			if($stmt->bindAndExecute('s', $format)) {
				if($row = $stmt->fetch_array()) {
					$className = importFromModule($row['controlClass'], $row['moduleId'], 'control'));
					if($className !== false) {
						$data = $row;
					}
				}
			}
			$cache->storeData($data);
		}

		if($data !== false) {
			$class = importFromModule($data['controlClass'], $data['moduleId'], 'control');
			try {
				$control = new $class();
			} catch (Exception $e) {}
			return $control;
		} else {
			return false;
		}
	}
}

?>