<?php

class LocationModel extends AbstractModel
{
	public $allowedChildrenTypes = array();
	protected $location;
	static public $fallbackModelActions = array('LocationBasedRead', 'LocationBasedAdd', 'LocationBasedEdit',
													'LocationBasedDelete', 'LocationBasedIndex');

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

			if($locationId)
				$this->location = $locationId;
		}
	}

	public function save()
	{
		$db = DatabaseConnection::getConnection('default');
		$db->autocommit(false);

		try
		{
			if(!parent:: save())
				throw new BentoError('Unable to save model');

			$location = $this->getLocation();

			if(!$location)
			{
				throw new BentoError('There is no location');
			}

			$location->setResource($this->getType(), $this->getId());
			$location->setName($this->properties['name']);

			if(!$location->save())
				throw new BentoError('Unable to save model location');

			if($parentLocation = $location->getParent())
				Cache::clear('locations', $parentLocation->getId(), 'children');

		}catch(Exception $e){
			$db->rollback();
			$db->autocommit(true);
			return false;
		}

		$db->autocommit(true);
		return true;
	}

	public function delete()
	{
		if(!isset($this->id))
			throw new BentoError('Attempted to delete unsaved model.');

		// delete location!
		$location = $this->getLocation();

		if($location->delete())
		{
			return parent::delete();
		}else{
			return false;
		}
	}

	public function setParent(Location $parent)
	{
		$parentModel = $parent->getResource();
		if(!$parentModel->canHaveChildType($this->getType()))
			throw new BentoError('Attempted to save ' . $this->getType() . ' to incompatible parent location.', 409);

		$location = $this->getLocation();
		$location->setParent($parent);
	}

	public function getAllowedChildrenTypes()
	{
		$hook = new Hook();
		$hook->loadModelPlugins($this, 'getAllowedChildrenTypes');

		$pluginChildren = $hook->getAllowedChildren();

		if(is_array($this->allowedChildrenTypes))
			$pluginChildren[] = $this->allowedChildrenTypes;

		return call_user_func_array('array_merge', $pluginChildren);
	}

	public function canHaveChildType($resourceType)
	{
		$modelList = $this->getAllowedChildrenTypes();
		return in_array($resourceType, $modelList);
	}

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

	protected function loadFallbackAction($actionName)
	{
		return parent::loadFallbackAction('LocationBased' . $actionName);
	}

	public function __toArray()
	{
		$array = parent::__toArray();
		$location = $this->getLocation();
		$locationInfo['id'] = $location->getId();
		$locationInfo['type'] = $location->getType();
		$locationInfo['createdOn'] = strtotime($location->getCreationDate());
		$locationInfo['lastModified'] = strtotime($location->getLastModified());
		$locationInfo['owner'] = $location->getOwner();
		$locationInfo['group'] = $location->getOwnerGroup();
		$locationInfo['name'] = $location->getName();
		$array = array_merge($array, $locationInfo);
		return $array;
	}
}

?>