<?php

class ControlSet
{
	protected $controls = array();
	protected $user;

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
					FROM dashboardControls
					WHERE userId = ?');
			$stmt->bindAndExecute('i', $this->user->getId());

			while($row = $stmt->fetch_array()) {
				$control = array('id' => $row['instanceId'], 'control' => $row['controlId'],
						 'location' => $row['locationId']);

				$set_stmt = $db->stmt_init();

				$set_stmt->prepare('SELECT settingName, settingKey
						    FROM dashboardControlSettings
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
}

?>