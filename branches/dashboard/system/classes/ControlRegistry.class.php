<?php

class ControlRegistry
{
	static public function registerControl($name, $format, $module, $class)
	{
		$db = DatabaseConnection::getConnection('default');
		$stmt = $db->stmt_init();
		$stmt->prepare('INSERT INTO controls (controlFormat, controlName, moduleId, controlClass)
				VALUES (?, ?, ?, ?)');
		$stmt->bindAndExecute('ssis', $format, $name, $module, $class);
	}

	static public function getControl($format, $name)
	{
		return self::getControls($format, $name);
	}

	static public function getControls($format, $name = null)
	{
		if(defined('INSTALLMODE') && INSTALLMODE == true)
			return false;

		if(isset($name)) {
			$cache = CacheControl::getCache('controls', $format, 'single', $name);
		} else {
			$cache = CacheControl::getCache('controls', $format, 'list');
		}

		$data = $cache->getData();
		if($cache->isStale()) {
			$data = array();
			$db = DatabaseConnection::getConnection('default_read_only');
			$stmt = $db->stmt_init();
			$sql = 'SELECT controlName, moduleId, controlClass
						FROM controls
						WHERE controlFormat = ?';
			if(isset($name)) {
				$sql .= ' AND controlName = ?';
			}
			$stmt->prepare($sql);

			if(isset($name)) {
				$success = $stmt->bindAndExecute('ss', $format, $name);
			} else {
				$success = $stmt->bindAndExecute('s', $format);
			}
			if($success) {
				while($row = $stmt->fetch_array()) {
					$className = importFromModule($row['controlClass'], $row['moduleId'], 'control');
					if($className !== false) {
						$data[] = $row;
					}
				}
			}
			$cache->storeData($data);
		}

		if (count($data) === 0) {
			return false;
		} elseif(isset($name)) {
			$row = array_shift($data);
			$class = importFromModule($row['controlClass'], $row['moduleId'], 'control');
			try {
				$control = new $class();
			} catch (Exception $e) {}
			return $control;
		} else {
			return $data;
		}
	}
}

?>