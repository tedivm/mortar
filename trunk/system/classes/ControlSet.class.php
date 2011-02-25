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
 * A ControlSet encapsulates the full set of Controls which are registered for display to a specific user. Using this
 * class, various parts of the system can extract the content of these controls, change their order and settings,
 * add and remove Controls altogether, and save changes directly to the database.
 *
 * @package System
 * @subpackage Dashboard
 */
class ControlSet
{
	/**
	 * Array of the Controls assigned to this user
	 *
	 * @access protected
	 * @var array
	 */
	protected $controls = array();

	/**
	 * Model instance of the User that this ControlSet is assigned to
	 *
	 * @access protected
	 * @var MortarCoreModelUser
	 */
	protected $user;

	/**
	 * Id of the User that this ControlSet is assigned to
	 *
	 * @access protected
	 * @var int
	 */
	protected $format = 'admin';

	/**
	 * Name of the table in which user controls are saved.
	 *
	 * @access protected
	 * @var string
	 */
	protected $controlsTable = 'dashboardControls';

	/**
	 * Name of the table in which settings for user controls are saved.
	 *
	 * @access protected
	 * @var string
	 */
	protected $settingsTable = 'dashboardControlSettings';

	/**
	 * Constructor takes the id of a user and loads the model associated with it
	 *
	 * @param string $name
	 */
	public function __construct($user) {
		$this->user = ModelRegistry::loadModel('User', $user);
	}

	/**
	 * Loads from the database the current list of controls registered to this user, along with their settings
	 * and order. This should always be called before modifying any settings intended to be saved since
	 * otherwise existing settings will be wiped out. 
	 *
	 */
	public function loadControls()
	{
		if(defined('INSTALLMODE') && INSTALLMODE == true)
			return false;

		if($this->user === false)
			return false;

		$cache = CacheControl::getCache('controls', 'admin', 'user', $this->user->getId());

		$data = $cache->getData();
		if($cache->isStale()) {
			$data = array();
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

	/**
	 * Adds a new control to the end of the current user's list based on its Id in the ControlRegistry.
	 * Optionally takes a location and any number of settings (in an array) to pre-initialize the new
	 * control with specific settings.
	 *
	 * @param int $id
	 * @param int $location = null
	 * @param array $settings = array()
	 * @return int
	 */
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

	/**
	 * Sets the value of the location field for the control currently in the provided position. Returns
	 * true if there's a control present in that position.
	 *
	 * @param int $pos
	 * @param int $location = null
	 * @return bool
	 */
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

	/**
	 * Sets the value of the settings array for the control currently in the provided position. Returns
	 * true if there's a control present in that position.
	 *
	 * @param int $pos
	 * @param int $settings = array()
	 * @return bool
	 */
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

	/**
	 * Takes the control in the listed position and moves it either up or down depending on the provided parameter.
	 * Returns true if the control in question could effectively be swapped in the listed direction, false if
	 * it could not.
	 *
	 * @param int $pos
	 * @param bool $up = true
	 * @return bool
	 */
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

	/**
	 * Removes the control currently occupying the specified position from the list of controls.
	 *
	 * @param int $pos
	 * @return true
	 */
	public function removeControl($pos)
	{
		array_splice($this->controls, $pos, 1);
		return true;
	}

	/**
	 * Removes all controls saved for the current user from the database. Is a prerequisite for saving the state of
	 * this ControlSet and is called by saveControls()
	 *
	 */
	public function clearControls()
	{
		$db = DatabaseConnection::getConnection('default');
		$stmt = $db->stmt_init();

		$stmt->prepare('DELETE FROM ' . $this->controlsTable . '
				WHERE userId = ?');
		$stmt->bindAndExecute('i', $this->user->getId());
	}

	/**
	 * Updates this class' array of control data by reading any changes from the Control objects themselves.
	 * NOTE: This entire class needs to be refactored to remove the necessity of this step, probably by
	 * having ControlBase implement the ArrayAccess interface and then using the controls themselves directly
	 * as array members for storage in this class.
	 *
	 */
	public function refreshControls()
	{
		foreach($this->controls as $key => $control) {
			$c = $control['class'];
			$this->controls[$key]['location'] = $c->getLocation();
			$this->controls[$key]['settings'] = $c->getSettings();
		}
	}

	/**
	 * Saves all details of the controls currently loaded into this class to the database.
	 *
	 */
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

	/**
	 * Returns an array of the control classes currently loaded to this set.
	 *
	 * @return array
	 */
	public function getControls()
	{
		$controls = array();
		foreach($this->controls as $control) {
			$controls[] = $control['class'];
		}

		return $controls;
	}

	/**
	 * Given a position number, returns the control class of the control in that position, or false
	 * if none is present.
	 *
	 * @param int $pos
	 * @return ControlBase|false
	 */
	public function getControl($pos)
	{
		if(isset($this->controls[$pos])) {
			return $this->controls[$pos]['class'];
		} else {
			return false;
		}
	}

	/**
	 * Returns an array of information (id, name, location, settings) about the controls in this user's
	 * current list.
	 *
	 * @return array
	 */
	public function getInfo()
	{
		$info = $this->controls;
		foreach($info as $key => $item) {
			unset($info[$key]['class']);
		}

		return $info;
	}

	/**
	 * Returns the id of the user this class is representing the controls of.
	 *
	 * @return int
	 */
	public function getUserId()
	{
		return $this->user->getId();
	}
}

?>