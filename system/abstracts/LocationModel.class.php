<?php

class LocationModel extends AbstractModel
{
	protected $location;
	static public $fallbackModelActions = array('LocationBasedRead', 'LocationBasedAdd', 'LocationBasedEdit', 'LocationBasedDelete');

	public function __construct($id = null)
	{
		parent::__construct($id);

		if(isset($this->id))
		{
			$cache = new Cache('models', $this->getType(), $id, 'location');
			$locationId = $cache->getData();

			if(!$cache->cacheReturned)
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
				$this->location = new Location($locationId);
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

			if(!$this->location)
			{
				if(!$parent)
					throw new BentoError('On creation a parent location is required.');
				$this->location = new Location();
			}

			if($parent)
			{
				if(!($parent instanceof Location))
					throw new TypeMismatch(array('Location', $parent));

				if(!$this->canSaveTo($parent->getType()))
					throw new BentoError('Unable to save to resource type ' . $parent->getType());

				$this->location->setParent($parent);
			}

			$this->location->setResource($this->getType(), $this->getId());
			$this->location->setName($this->properties['name']);

			if(!$this->location->save())
				throw new BentoError('Unable to save model location');

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
		if(!$this->canSaveTo($parent->getType()))
			throw new BentoError('Attempted to save ' . $this->getType() . ' to incompatible parent location.', 409);

		$location = $this->getLocation();
		$location->setParent($parent);
	}

	public function canSaveTo($resourceType)
	{
		if(in_array($resourceType, $this->allowedParents))
			return true;

		return false;
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
		}
		return $this->location;
	}

	protected function loadFallbackAction($actionName)
	{
		return parent::loadFallbackAction('LocationBased' . $actionName);
	}
}

?>