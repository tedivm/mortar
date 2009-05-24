<?php

// this class is going to take over all of the Model function that don't have to do with location, and shorten the ones
// that do so that they don't. Then we're going to make a LocationModel class, which will inherit this and take over all
// the location specific functionality of the current Model class. Then the Model class will be deleted, with this being
// the final replacement.


class SpecialModel
{
	static public $type = 'User';

	protected $table = 'users';
	protected $location = false;

	static public $fallbackModelActions = array('Read', 'Add', 'Edit', 'Delete');

	public function __construct($id = null)
	{

	}

	public function save()
	{
			if(isset($this->table))
			{
				$record = new ObjectRelationshipMapper($this->table);

				if(isset($this->id))
					$record->primaryKey = $this->id;

				$columns = $record->getColumns(false, false);

				foreach($columns as $columnName)
				{
					if(isset($this->content[$columnName]))
						$record->$columnName = $this->content[$columnName];
				}

				if(!$record->save())
					throw new BentoError('Unable to save model information to table');

				if(!isset($this->id))
					$this->id = $record->primaryKey;
			}
	}

	public function delete()
	{
		if(!isset($this->id))
			throw new BentoError('Attempted to delete unsaved model.');

		if(isset($this->table))
		{
			$record = new ObjectRelationshipMapper($this->table);
			$record->primaryKey($this->id);

			if($record->select(1))
			{
				$record->delete(1);
			}else{

			}
		}
	}

	public function checkAuth($action, $user = null)
	{

	}


	public function __toArray()
	{

	}

	public function getAction($actionName)
	{
		$packageInfo = new PackageInfo($this->module);
		$moduleActionName = $this->getType() . $actionName;
		$actionInfo = $packageInfo->getActions($moduleActionName);

		if(!$actionInfo)
		{
			if(in_array($actionName, staticHack(get_class($this), 'fallbackModelActions'))
				|| ($actionName == 'Execute' && method_exists($this, 'execute')) )
			{
				$actionInfo['className'] = 'ModelAction' . $actionName;
				$config = Config::getInstance();
				$actionInfo['path'] = $config['path']['mainclasses'] . 'models/actions/' . $actionName . '.class.php';
			}else{
				return false;
			}
		}
		return $actionInfo;
	}

	public function getContent()
	{
		return $this->content;
	}

	public function getId()
	{
		return $this->id;
	}




	public function getProperties()
	{
		return $this->properties;
	}

	public function getType()
	{
		return staticHack(get_class($this), 'type');
	}

	public function getModule()
	{
		return $this->module;
	}


	public function getLocation()
	{

	}

	public function setParent(Location $parent)
	{

	}

	public function canSaveTo($resourceType)
	{

	}

	// class properties define attributes and meta data
	public function __get($offset)
	{
		if(isset($this->properties[$offset]))
			return $this->properties[$offset];
	}

	public function __set($offset, $value)
	{
		if(!is_scalar($value))
			throw new BentoError('Model attributes must be scalar.');

		return $this->properties[$offset] = $value;
	}

	public function __isset($offset)
	{
		return isset($this->properties[$offset]);
	}

	public function __unset($offset)
	{
		unset($this->properties[$offset]);
	}




	// array functions define content and the actual substance of the class
	public function offsetGet($name)
	{
		return isset($this->content[$name]) ? $this->content[$name] : null;
	}

	public function offsetSet($name, $value)
	{
		return $this->content[$name] = $value;
	}

	public function offsetExists($name)
	{
		return isset($this->content[$name]);
	}

	public function offsetUnset($name)
	{
		unset($this->content[$name]);
	}


}

?>