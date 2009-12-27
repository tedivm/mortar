<?php

abstract class LocationModel extends ModelBase
{
	/**
	 * This array contains a list (currently empty) of all children that the current model can inherit. This is empty
	 * and should be redefined by any inheriting classes which need to attach child objects. It can also be redefined
	 * during runtime using a plugin.
	 *
	 * @var array
	 */
	public $allowedChildrenTypes = array();

	/**
	 * This is the location object the model is attached to.
	 *
	 * @var Location
	 */
	protected $location;

	/**
	 * This attributes defined how locations are named. If false then the name of a new location must be set before it
	 * can be saved, otherwise a name will be created based off of the models id and type.
	 *
	 * @var bool
	 */
	static public $autoName = false;

	/**
	 * This specificies what folder inside the modelSupport folder contains the model's fallback actions. Each array
	 * element is an additional directory level and name.
	 *
	 * @var array
	 */
	protected $backupActionDirectory = array('actions', 'LocationBased');

	/**
	 * This is the prefix added to fallback actions to get the name of the specific class the action uses. For instance,
	 * calling the 'Read' action would result in using the class ModelActionLocationBasedRead inside the
	 * system/modelSupport/actions/LocationBased/Read.class.php file.
	 *
	 * @var string
	 */
	protected $fallbackActionString = 'ModelActionLocationBased';

	/**
	 * This array contains the list of actions that have a fall back handler in the classes/modelSupport folder. This
	 * should be overloaded if eliminating or using different classes.
	 *
	 * @var array
	 */
	static public $fallbackModelActions = array('Read', 'Add', 'Edit', 'Delete', 'Ownership', 'GroupPermissions',
		'EditGroupPermissions', 'UserPermissions', 'EditUserPermissions', 'Index');

	static public $fallbackModelActionNames = array('Index' => 'Browse', 'GroupPermissions' => 'Group Permissions',
		'UserPermissions' => 'User Permissions');

	/**
	 * If set this is used as the default status for a new model.
	 *
	 * @var string
	 */
	static public $defaultStatus;

	/**
	 * This function contains an array of strings that can be used as a status type for a model.
	 *
	 * @var array
	 */
	static public $statusTypes = array();

	/**
	 * The constructor sets some basic properties and, if an ID is passed, initializes the model. It runs the parent
	 * constructor to load any table references, like a regular model, and then if that was successful it loads the
	 * location information associated with that model.
	 *
	 * @param int|null $id
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);

		if(isset($this->id))
		{
			$cache = new Cache('models', $this->getType(), $id, 'location');
			$locationId = $cache->getData();

			if($cache->isStale())
			{
				$db = DatabaseConnection::getConnection('default_read_only');
				$stmt = $db->stmt_init();
				$stmt->prepare('SELECT location_id FROM locations WHERE resourceType = ? AND resourceId = ? LIMIT 1');
				$stmt->bindAndExecute('si', $this->getType(), $id);

				if($stmt->num_rows > 0)
				{
					$locationArray = $stmt->fetch_array();
					$locationId = $locationArray['location_id'];
					$cache->storeData($locationId);
				}else{
					$locationId = false;
				}
			}

			if(isset($locationId) && is_numeric($locationId))
			{
				$this->location = new Location($locationId);
				$this->properties['name'] = $this->location->getName();
				$this->properties['status'] = $this->location->getStatus();
			}else{
				if(!isset($this->properties['status']) && $status = staticHack(get_class($this), 'defaultStatus'))
					$this->properties['status'] = $status;
			}
		}
	}

	/**
	 * This function runs the parent save function and then, if that is successful, it saved the location data. Even if
	 * the location data has not changed it is still saved to update the 'lastModified' date stored by the location. If
	 * no name is set or autoname is enabled then a name based off of the model type and id will be created for the
	 * location.
	 *
	 * @return bool
	 */
	public function save()
	{
		$db = DatabaseConnection::getConnection('default');
		$db->autocommit(false);

		try
		{
			$isFirstSave = !isset($this->id);

			if(!parent:: save())
				throw new CoreError('Unable to save model');

			$location = $this->getLocation();

			if(!$location)
			{
				throw new CoreError('There is no location');
			}

			$location->setResource($this->getType(), $this->getId());


			// I'm kind of torn on whether I should force the autoname for models that request it, or leave the
			// option open for programmers who feel the need to give custom names, so I'm siding on the less restrictive
			// choice for now.
			if(!isset($this->properties['name'])
					|| $this->properties['name'] == 'tmp'
					|| staticHack($this, 'autoName') === true)
			{
				$name = strtolower($this->getType()) . '_' . $this->getId();
				$location->setName($name);
			}elseif(isset($this->properties['name'])){
				$location->setName($this->properties['name']);
			}

			if(isset($this->properties['status']))
				$location->setStatus($this->properties['status']);

			if(!$location->getOwnerGroup() && $parent = $location->getParent())
			{
				if($parentMemberGroup = $location->getOwnerGroup())
				{
					$location->setOwnerGroup($parentMemberGroup->getId());
					$location->save();
				}
			}

			if(!$location->save())
				throw new CoreError('Unable to save model location');

			if($parentLocation = $location->getParent())
				Cache::clear('locations', $parentLocation->getId(), 'children');

		}catch(Exception $e){
			$db->rollback();
			$db->autocommit(true);
			return false;
		}

		$db->autocommit(true);

		if(isset($isFirstSave) && $isFirstSave == true) {
			$this->firstSaveLocation();

			$hook = new Hook();
			$hook->loadModelPlugins($this, 'firstSave');
			$hook->runFirstSave($this);
		}

		return true;
	}

	/**
	 * This function acts as a hook for inheriting classes. It is called the first time a model is saved, after its
	 * location has been saved.
	 *
	 */
	protected function firstSaveLocation()
	{

	}

	/**
	 * This function returns the different types of status a model can have.
	 *
	 * @hook *model getAllowedStatusTypes
	 * @return array
	 */
	public function getStatusTypes()
	{
		$types = staticHack(get_class($this), 'statusTypes');

		$hook = new Hook();
		$hook->loadModelPlugins($this, 'getAllowedStatusTypes');
		$extraTypes = Hook::mergeResults($hook->getExtraTypes());

		$allTypes = array_merge($types, $extraTypes);
		return $allTypes;
	}

	/**
	 * This function first deletes its location and then runs the parent delete function.  This function has a high run
	 * cost because it recursively deletes all of its children models (which can potentially be an insanely high
	 * amount). For this reason we avoid calling this function directly when deleting models with the user interface,
	 * instead we take the location and change it's parent to the trash bin. This function will then get called by an
	 * admin or some sort of clean up cron job.
	 *
	 * @return bool Either it returns true or it throws some sort of exception describing why it did not delete.
	 */
	public function delete()
	{
		if(!isset($this->id))
			throw new CoreError('Attempted to delete unsaved model of the type ' . $this->getType());

		try
		{
			$db = DatabaseConnection::getConnection('default');
			$db->autocommit(false);
			// delete location!
			$location = $this->getLocation();

			Cache::clear('locations', $location->getId());
			Cache::clear('models', $this->getType(), $this->getId());
			$location = new Location();

			if($children = $location->getChildren())
			{
				foreach($children as $child)
				{
					$childModel = $child->getResource();
					if(!$childModel->delete())
						throw new CoreError('Unable to delete child location ' . (string) $child);
				}
			}

			if(!parent::delete())
				throw new CoreError('Unable to delete model information location ' . (string) $child);

			if(!$location->delete())
				throw new CoreError('Unable to delete child location ' . (string) $child);

			$db->autocommit(true);
			Cache::clear('locations', $location->getId());
			Cache::clear('models', $this->getType(), $this->getId());
			unset($this->location);
			unset($this->id);

			// Deleting an object with a lot of children can take quite a bit of time, so each time we successfully
			// delete an object we extend the time the script can run. On unix this doesn't include time waiting on
			// database calls or io, but on windows (which we have never even tried running this on) it does not.
			set_time_limit(ini_get('max_execution_time'));

			return true;
		}catch(Exception $e){
			$db->rollback();
			$db->autocommit(true, true);
			throw $e;
		}
	}

	/**
	 * This function takes in a location, checks to make sure that location can take this model type, and then sets that
	 * location as its parent.
	 *
	 * @param Location $parent
	 */
	public function setParent(Location $parent)
	{
		$parentModel = $parent->getResource();
		if(!(defined('INSTALLMODE') && INSTALLMODE) && !$parentModel->canHaveChildType($this->getType()))
			throw new CoreError('Attempted to save ' . $this->getType() . ' to incompatible parent location.', 409);

		$location = $this->getLocation();
		$location->setParent($parent);
	}

	/**
	 * This function returns an array of model types that this model is allowed to inherit. This array is a combination
	 * of the $allowedChildrenTypes property and installed plugins.
	 *
	 * @hook $this getAllowedChildrenTypes
	 * @return array
	 */
	public function getAllowedChildrenTypes()
	{
		$hook = new Hook();
		$hook->loadModelPlugins($this, 'getAllowedChildrenTypes');

		$pluginChildren = $hook->getAllowedChildren();

		if(is_array($this->allowedChildrenTypes))
			$pluginChildren[] = $this->allowedChildrenTypes;

		return call_user_func_array('array_merge', $pluginChildren);
	}

	public function getUrl()
	{
		$url = new Url();
		$location = $this->getLocation();
		$url->locationId = $location->getId();
		return $url;
	}

	/**
	 * This function takes in a model type and checks to see if it is allowed to be attached to this model.
	 *
	 * @param string $resourceType
	 * @return bool
	 */
	public function canHaveChildType($resourceType)
	{
		$modelList = $this->getAllowedChildrenTypes();
		return in_array($resourceType, $modelList);
	}

	/**
	 * This function returns the location the model is currently attached to. If the model has not been saved yet it
	 * creates a new location with the name 'tmp' and no parent. If the name is tmp and autonaming is enabled the name
	 * will change before being saved.
	 *
	 * @return Location
	 */
	public function getLocation()
	{
		if(!isset($this->location))
		{
			$this->location = new Location();
			$id = (isset($this->id)) ? $this->id : 0;
			$name = (isset($this->properties['name'])) ? $this->properties['name'] : 'tmp';
			$this->location->setResource($this->getType(), $id);
			$this->location->setName($name);

		}elseif(is_numeric($this->location)){
			$this->location = new Location($this->location);
		}

		if(!($this->location instanceof Location))
			return false;

		return $this->location;
	}

	/**
	 * This function checks to see if a specified action can be performed by a specific user on this model. This is
	 * based off of the model's location and can be very specific (allowing all location based models to have their own
	 * custom permissions).
	 *
	 * @param string $action
	 * @param User|int|null $user If no user is passed the active user is retrieved and used.
	 * @return bool
	 */
	public function checkAuth($action, $user = null)
	{
		if(!isset($user))
			$user = ActiveUser::getUser();

		$location = $this->getLocation();
		$permissionObject = new Permissions($location, $user);
		$type = $this->getType();
		return $permissionObject->isAllowed($action, $type);
	}

	/**
	 * This function expands on the parent function to add specific location information to the returned array.
	 *
	 * @return array
	 */
	public function __toArray()
	{
		$array = parent::__toArray();
		$location = $this->getLocation();
		$locationInfo['id'] = $location->getId();
		$locationInfo['type'] = $location->getType();
		$locationInfo['createdOn'] = $location->getCreationDate();
		$locationInfo['lastModified'] = $location->getLastModified();
		$locationInfo['publishDate'] = $location->getPublishDate();
		$locationInfo['owner'] = $location->getOwner();
		$locationInfo['group'] = $location->getOwnerGroup();
		$locationInfo['name'] = $location->getName();
		$array = array_merge($array, $locationInfo);
		return $array;
	}

	/**
	 * This extends the (placeholder) parent function and returns an array of Urls for the
	 * various actions which can be performed by this Model.
	 *
	 * @param string $format
	 * @param array|null $attributes
	 * @return array
	 */
	public function getActionUrls($format, $attributes = null)
	{
		$actionUrls = array();

		$baseUrl = new Url();
		$baseUrl->locationId = $this->location->getId();
		$baseUrl->format = $format;

		$actions = $this->getActions();

		foreach($actions as $actionName => $action) {
			$actionUrl = clone $baseUrl;
			$actionUrl->action = $actionName;

			if (isset($attributes))
				foreach($attributes as $attName => $attValue)
					$actionUrl->property($attName, $attValue);

			$actionUrls[$actionName] = $actionUrl;
		}

		return $actionUrls;
	}

	/**
	 * Extends the parent function, returning correct values for a variety of location-specific qualities and
	 * otherwise passing back the parent function's results.
	 *
	 * @return object
	 */
	public function __get($offset)
	{
		$location = $this->getLocation();
		switch($offset) {
			case 'owner':
				return $location->getOwner();
			case 'ownergroup':
				return $location->getOwnerGroup();
			case 'createdOn':
				return $location->getCreationDate();
			case 'lastModified':
				return $location->getLastModified();
			case 'publishDate':
				return $location->getPublishDate();
			case 'name':
				return $location->getName();
		}
		return parent::__get($offset);
	}

	public function __isset($offset)
	{
		switch($offset) {
			case 'owner':
			case 'ownergroup':
			case 'createdOn':
			case 'lastModified':
			case 'publishDate':
			case 'name':
				return true;
		}
		return parent::__isset($offset);
	}

	public function __set($offset, $value)
	{
		if(!is_scalar($value))
			throw new CoreError('Model attributes must be scalar.');

		if (in_array($value, array('owner', 'ownergroup', 'createdOn', 'lastModified', 'publishDate', 'name')))
			return false;

		return parent::__set($offset, $value);
	}
}

?>
