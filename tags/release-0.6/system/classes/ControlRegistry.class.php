<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Dashboard
 */

/**
 * The ControlRegistry is a class accessed through a series of static methods in order to register and return
 * information about Controls. A module that includes Controls should register them through this class during
 * installation, after which they will become available to any and all systems that utilize Controls.
 *
 * @package System
 * @subpackage Dashboard
 */
class ControlRegistry
{

	/**
	 * Registers a control to the database. $format determines in what contexts the control is usable.
	 * This should be 'admin' for use on the Dashboard; other contexts are possible but not yet implemented.
	 * If a control can be used in multiple contexts, it should be registered once with each allowed format.
	 * $module is the id of the module in which the Control is installed. $class is the shortname for
	 * the class of the Module, not including module/type info -- so for "MortarControlCoffeeCup" this
	 * value should be "CoffeeCup"
	 *
	 * @param string $format
	 * @param int $module
	 * @param string $class
	 * @return bool
	 */
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

	/**
	 * Returns a class instance for a given Control given a format and name. Optionally accepts a location
	 * and settings array to be initialized into the control before being returned.
	 *
	 * @param string $format
	 * @param string $name
	 * @param $location = null
	 * @param array $settings = array()
	 * @return ControlBase
	 */
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

	/**
	 * Given the database id of a given control, returns an info array (class, id, name, module) containing
	 * the relevant information regarding that control, or false if the id is not present in the registry.
	 *
	 * @param int $id
	 * @return array|false
	 */
	static public function getControlInfoById($id)
	{
		$data = self::loadControls(null, null, $id);
		if(isset($data[0])) {
			return $data[0];
		} else {
			return false;
		}
		
	}

	/**
	 * Given the format and name of a given control, returns an info array (class, id, name, module) containing
	 * the relevant information regarding that control, or false if the id is not present in the registry.
	 *
	 * @param string $format
	 * @param string $name
	 * @return array|false
	 */
	static public function getControlInfo($format, $name)
	{
		$result = self::loadControls($format, $name);
		if(isset($result[0])) {
			return $result[0];
		} else {
			return false;
		}
	}

	/**
	 * Returns an array containing information (class, id, name, module) for all controls registered to a given
	 * format, or false if no such controls are found.
	 *
	 * @param string $format
	 * @return array|false
	 */
	static public function getControls($format)
	{
		return self::loadControls($format);
	}

	/**
	 * Helper function that takes the direct output from the database and reformats it into the consistent return
	 * structure used by the above wrapper functions.
	 *
	 * @param array $list
	 * @return array
	 */
	static protected function renameElements($list)
	{
		$info = array();
		foreach($list as $control) {
			$info[] = array('id' => $control['controlId'], 'name' => $control['controlName'], 
				'class' => $control['controlClass'], 'module' => $control['moduleId']);
		}
		return $info;
	}

	/**
	 * Actual database access function for the wrapper functions above. Operates in three modes: with format
	 * and name, with format only, and with id only. 
	 *
	 * @cache controls *format *scope *param *value
	 * @param string $format = 'admin'
	 * @param string $name = null
	 * @param int $id = null
	 * @return array|false
	 */
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