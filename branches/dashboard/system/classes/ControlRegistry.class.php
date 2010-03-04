<?php

class ControlRegistry
{
	static public function registerControl($format, $module, $class)
	{
		$className = importFromModule($class, $module, 'control');
		if($className === false)
			return false;

		try {
			$classI = new $className($format, null, null);
			$name = $classI->getName();
		} catch (Exception $e) {
			return false;
		}

		$db = DatabaseConnection::getConnection('default');
		$stmt = $db->stmt_init();
		$stmt->prepare('INSERT INTO controls (controlFormat, controlName, moduleId, controlClass)
				VALUES (?, ?, ?, ?)');
		if($stmt->bindAndExecute('ssis', $format, $name, $module, $class)) {
			return true;
		} else {
			return false;
		}
	}

	static public function getControl($format, $name, $location = null, $settings = array())
	{
		$data = self::loadControls($format, $name);
		$row = array_shift($data);
		$class = importFromModule($row['class'], $row['module'], 'control');
		try {
			$control = new $class($format, $location, $settings);
		} catch (Exception $e) {}
		return $control;
	}

	static public function getControlInfoById($id)
	{
		$data = self::loadControls(null, null, $id);
		if(isset($data[0])) {
			return $data[0];
		} else {
			return false;
		}
		
	}

	static public function getControlInfo($format, $name)
	{
		$result = self::loadControls($format, $name);
		if(isset($result[0])) {
			return $result[0];
		} else {
			return false;
		}
	}

	static public function getControls($format)
	{
		return self::loadControls($format);
	}

	static protected function renameElements($list)
	{
		$info = array();
		foreach($list as $control) {
			$info[] = array('id' => $control['controlId'], 'name' => $control['controlName'], 
				'class' => $control['controlClass'], 'module' => $control['moduleId']);
		}
		return $info;
	}

	static protected function loadControls($format = 'admin', $name = null, $id = null)
	{
		if(defined('INSTALLMODE') && INSTALLMODE == true)
			return false;

		if(isset($name)) {
			$cache = CacheControl::getCache('controls', $format, 'single', 'name', $name);
		} elseif(isset($id)) {
			$cache = CacheControl::getCache('controls', $format, 'single', 'id', $id);
		} else {
			$cache = CacheControl::getCache('controls', $format, 'list');
		}

		$data = $cache->getData();
		if($cache->isStale()) {
			$data = array();
			$db = DatabaseConnection::getConnection('default_read_only');
			$stmt = $db->stmt_init();
			$sql = 'SELECT controlId, controlName, moduleId, controlClass
				FROM controls ';
			if(isset($name)) {
				$sql .= 'WHERE controlFormat = ? AND controlName = ?';
			} elseif(isset($id)) {
				$sql .= 'WHERE controlId = ?';
			} else {
				$sql .= 'WHERE controlFormat = ?';
			}

			$stmt->prepare($sql);

			if(isset($name)) {
				$success = $stmt->bindAndExecute('ss', $format, $name);
			} elseif(isset($id)) {
				$success = $stmt->bindAndExecute('i', $id);
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
				$data = self::renameElements($data);
			}
			$cache->storeData($data);
		}

		if (count($data) === 0) {
			return false;
		} else {
			return $data;
		}
	}
}

?>