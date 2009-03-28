<?php

class FilteredArray extends ArrayObject
{
	protected $filters = array();
	protected $original = array();

	public function __construct(array &$array)
	{
		parent::__construct($array);
		$this->original = &$array;
		$this->setIteratorClass('FilteredArrayIterator');
	}

	public function addFilter(Filter $filterObject)
	{
		$this->filters[] = $filterObject;
	}

	public function setFilters($filterArray)
	{
		$this->filters = $filterArray;
	}

	public function clearFilters()
	{
		$this->filters = array();
	}

	public function getUnfiltered($offset)
	{
		return $this[$offset];
	}

	public function offsetGet($offset)
	{
		if(parent::offsetExists($offset))
			return parent::offsetGet($offset);
	}

	public function getIterator()
	{
		$iterator = parent::getIterator();
		$iterator->setArrayReturnClass(get_class($this));
		$iterator->setFilters($this->filters);
		return $iterator;
	}

	public function save()
	{
		$this->original = $this->getArrayCopy();
	}

	protected function filterOutput($value)
	{
		if(is_array($value))
		{
			$className = get_class($this);
			$temp =  new $className($value);
			$temp->setFilters($this->filters);
			return $temp;

		}elseif(is_bool($value)){

			return $value;

		}else{
			$temp = $value;
			foreach($this->filters as $filter)
			{
				$temp = $filter->filter($temp);
			}
			return $temp;
		}
	}

}

class FilteredArrayIterator extends ArrayIterator
{
	protected $filters;
	protected $arrayReturnClass = 'ArrayObject';

	public function setFilters($filters)
	{
		$this->filters = $filters;
	}

	public function current()
	{
		return $this->filterOutput(parent::current());
	}

	public function setArrayReturnClass($class)
	{
		if(!class_exists($class))
			throw new BentoError('Class ' . $class . ' does not exist.');

		$this->arrayReturnClass = $class;
	}

	protected function filterOutput($value)
	{
		if(is_array($value))
		{
			$className = $this->arrayReturnClass;
			$temp =  new $className($value);
			$temp->setFilters($this->filters);
			return $temp;

		}elseif(is_bool($value)){

			return $value;

		}else{
			$temp = $value;
			foreach($this->filters as $filter)
			{
				$temp = $filter->filter($temp);
			}
			return $temp;
		}
	}
}

?>