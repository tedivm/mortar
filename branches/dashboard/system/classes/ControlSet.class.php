<?php

class ControlSet
{
	protected $controls = array();
	protected $user;

	protected $format = 'admin';
	protected $controlsTable = 'dashboardControls';
	protected $settingsTable = 'dashboardControlSettings';

	public function __construct($user) {
		$this->user = ModelRegistry::loadModel('User', $user);
	}

	public function loadControls()
	{
		if(defined('INSTALLMODE') && INSTALLMODE == true)
			return false;

		if($this->user === false)
			return false;

		$cache = CacheControl::getCache('controls', 'admin', 'user', $this->user->getId());

		$data = $cache->getData();
		if($cache->isStale()) {
			$db = DatabaseConnection::getConnection('default_read_only');
			$stmt = $db->stmt_init();

			$stmt->prepare('SELECT sequence, controlId, locationId
					FROM ' . $this->controlsTable . '
					WHERE userId = ?');
			$stmt->bindAndExecute('i', $this->user->getId());

			while($row = $stmt->fetch_array()) {
				$control = array('id' => $row['controlId'],
						 'location' => $row['locationId']);

				$byId = ControlRegistry::getControlInfoById($row['controlId']);
				$control['name'] = $byId['name'];

				$set_stmt = $db->stmt_init();

				$set_stmt->prepare('SELECT settingName, settingKey
						    FROM ' . $this->settingsTable . '
						    WHERE userId = ? AND sequence = ?');
				$set_stmt->bindAndExecute('ii', $this->user->getId(), $row['sequence']);

				$settings = array();
				while($set_row = $set_stmt->fetch_array()) {
					$settings[$set_row['settingName']] = $set_row['settingKey'];
				}
				$control['settings'] = $settings;

				$control['class'] = ControlRegistry::getControl($this->format, 
					$control['name'], $control['location'], $control['settings']);

				$data[$row['sequence']] = $control;
			}

			$cache->storeData($data);
		}
		if ($data === false) {
			$this->controls = array();
		} else {
			$this->controls = $data;
		}
	}

	public function addControl($id, $location = null, $settings = array())
	{
		if(!($info = ControlRegistry::getControlInfoById($id)))
			return false;

		if(!is_array($settings))
			$settings = array();

		$control = array('id' => $info['id'], 'settings' => $settings, 'name' => $info['name']);

		if(isset($location))
			$control['location'] = $location;

		$control['class'] = ControlRegistry::getControl($this->format, $info['name'], $location, $settings);

		$this->controls[] = $control;
		
		return count($this->controls);
	}

	public function setLocation($pos, $location = null)
	{
		if(isset($this->controls[$pos])) {
			$this->controls[$pos]['location'] = $location;
			$class = $this->controls[$pos]['class'];
			$class->setLocation($location);
			return true;
		} else {
			return false;
		}
	}

	public function setSettings($pos, $settings = array())
	{
		if(isset($this->controls[$pos]) && is_array($settings)) {
			$this->controls[$pos]['settings'] = $settings;
			$class = $this->controls[$pos]['class'];
			$class->setSettings($settings);
			return true;
		} else {
			return false;
		}
	}

	public function swapControls($pos, $up = true)
	{
		if(!isset($this->controls[$pos]))
			return false;

		if($up && !isset($this->controls[$pos - 1]))
			return false;

		if(!$up && !isset($this->controls[$pos + 1]))
			return false;

		$swappee = $up ? $this->controls[$pos - 1] : $this->controls[$pos + 1];
		if($up) {
			$this->controls[$pos - 1] = $this->controls[$pos];
		} else {
			$this->controls[$pos + 1] = $this->controls[$pos];
		}
		$this->controls[$pos] = $swappee;

		return true;
	}

	public function removeControl($pos)
	{
		array_splice($this->controls, $pos, 1);
		return true;
	}

	public function clearControls()
	{
		$db = DatabaseConnection::getConnection('default');
		$stmt = $db->stmt_init();

		$stmt->prepare('DELETE FROM ' . $this->controlsTable . '
				WHERE userId = ?');
		$stmt->bindAndExecute('i', $this->user->getId());
	}

	public function refreshControls()
	{
		foreach($this->controls as $key => $control) {
			$c = $control['class'];
			$this->controls[$key]['location'] = $c->getLocation();
			$this->controls[$key]['settings'] = $c->getSettings();
		}
	}

	public function saveControls()
	{
		$this->clearControls();
		$this->refreshControls();

		$db = DatabaseConnection::getConnection('default');

		foreach($this->controls as $key => $control) {
			$stmt = $db->stmt_init();

			if(isset($control['location'])) {
				$stmt->prepare('INSERT INTO ' . $this->controlsTable . '
						(sequence, controlId, userId, locationId)
						VALUES (?, ?, ?, ?)');

				$stmt->bindAndExecute('iiii', $key, $control['id'], $this->user->getId(), 
					$control['location']);
			} else {
				$stmt->prepare('INSERT INTO ' . $this->controlsTable . '
						(sequence, controlId, userId)
						VALUES (?, ?, ?)');

				$stmt->bindAndExecute('iii', $key, $control['id'], $this->user->getId());
			}

			$control['id'] = $stmt->insert_id;

			foreach($control['settings'] as $name => $val) {
				$setting_stmt = $db->stmt_init();

				$setting_stmt->prepare('INSERT INTO ' . $this->settingsTable . '
							(userId, sequence, settingName, settingKey)
							VALUES (?, ?, ?, ?)');

				$setting_stmt->bindAndExecute('iiss', $this->user->getId(), $key, $name, $val);
			}
		}
	}

	public function getControls()
	{
		$controls = array();
		foreach($this->controls as $control) {
			$controls[] = $control['class'];
		}

		return $controls;
	}

	public function getControl($pos)
	{
		if(isset($this->controls[$pos])) {
			return $this->controls[$pos]['class'];
		} else {
			return false;
		}
	}

	public function getInfo()
	{
		$info = $this->controls;
		foreach($info as $key => $item) {
			unset($info[$key]['class']);
		}

		return $info;
	}

	public function getUserId()
	{
		return $this->user->getId();
	}
}

?>