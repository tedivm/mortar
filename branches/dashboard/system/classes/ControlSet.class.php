<?php

class ControlSet
{
	protected $controls = array();
	protected $user;

	protected $format = 'admin';
	protected $controlsTable = 'dashoardControls';
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

			$stmt->prepare('SELECT instanceId, sequence, controlId, locationId
					FROM ' . $this->controlsTable . '
					WHERE userId = ?');
			$stmt->bindAndExecute('i', $this->user->getId());

			while($row = $stmt->fetch_array()) {
				$control = array('id' => $row['instanceId'], 'control' => $row['controlId'],
						 'location' => $row['locationId']);

				$set_stmt = $db->stmt_init();

				$set_stmt->prepare('SELECT settingName, settingKey
						    FROM ' . $this->settingsTable . '
						    WHERE instanceId = ?');
				$set_stmt->bindAndExecute('i', $row['instanceId']);

				$settings = array();
				while($set_row = $set_stmt->fetch_array()) {
					$settings[$set_row['settingName']] = $set_row['settingKey'];
				}
				$control['settings'] = $settings;

				$data[$row['sequence']] = $control;
			}

			$cache->storeData($data);
		}
		$this->controls = $data;
	}

	public function addControl($name, $location = null, $settings = array())
	{
		if(!($info = ControlRegistry::getControlInfo($this->format, $name)))
			return false;

		if(!is_array($settings))
			$settings = array();

		$control = array('id' => 'unsaved', 'control' => $info['id'], 'settings' => $settings);

		$this->controls[] = $control;
	}

	public function setLocation($id, $location = null)
	{
		if(isset($this->controls[$id])) {
			$this->controls[$id]['location'] = $location;
			return true;
		} else {
			return false;
		}
	}

	public function setSettings($id, $settings = array())
	{
		if(isset($this->controls[$id]) && is_array($settings)) {
			$this->controls[$id]['settings'] = $settings;
			return true;
		} else {
			return false;
		}
	}

	public function clearControls()
	{
		$db = DatabaseConnection::getConnection('default');
		$stmt = $db->stmt_init();

		$stmt->prepare('DELETE FROM ' . $this->controlsTable . '
				WHERE userId = ?');
		$stmt->bindAndExecute('i', $this->user->getId());
	}

}

?>