<?php

abstract class Model
{
	protected $type;
	protected $location;
	protected $id;




	public function __construct($locationId = false)
	{
		if($locationId)
		{
			if(!is_numeric($locationId))
				throw new TypeMismatch(array('integer', $locationId));

			$location = new Location($locationId);
		}
	}

	public function name($name = NULL)
	{
		if(!is_null($name))
			$this->location->name = $name;

		return $this->location->getName();
	}

	public function createdDate($date = NULL)
	{
		if(!is_null($date))
		{
			//save this
			date('Y-m-d H:i:s', $date);
		}

		return strtotime($this->location->createdOn());
	}

	public function getId()
	{
		return $this->location->getId();
	}

	public function getParent()
	{
		return $this->location->getParent();
	}

	public function save($saveToLocation = false)
	{
		if(isset($this->location))
		{
			$location = $this->location;
		}else{
			$location = new Location();
		}

		if($saveToLocation)
		{
			if(is_numeric($saveToLocation))
				$saveToLocation = new Location($saveToLocation);

			if(!($saveToLocation instanceof Location))
				throw new TypeMismatch('Location', $saveToLocation, 'Other acceptable types are integer and null.');

			$location->parent = $saveToLocation->getId();
		}

		$location->name = $this->name;
		$location->parent = ($this->parent instanceof Location) ? $this->parent->getId() : $this->parent;
		$location->save();

		$this->location = $location;

	}

}

?>