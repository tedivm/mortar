<?php

/**
 * This is an extention to the ArrayObject class which applies filters to the output.
 *
 * @package Library
 * @subpackage Filters
 */
class FilteredArray extends ArrayObject
{
	/**
	 * This is an array of filter objects that the output gets run through.
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * This is a reference to the original array that can be used to save back the changes from this array.
	 *
	 * @var array
	 */
	protected $original = array();

	/**
	 * This constructor takes an array. It also overloads the ArrayIterator to use the FilteredArrayIterator.
	 *
	 * @param array $array
	 */
	public function __construct(array &$array)
	{
		parent::__construct($array);
		$this->original = &$array;
		$this->setIteratorClass('FilteredArrayIterator');
	}

	/**
	 * This function adds a new filter to the object.
	 *
	 * @param Filter $filterObject
	 */
	public function addFilter(Filter $filterObject)
	{
		$this->filters[] = $filterObject;
	}

	/**
	 * This takes an array of filters and overrides the current array with it.
	 *
	 * @param array $filterArray
	 */
	public function setFilters($filterArray)
	{
		$this->filters = $filterArray;
	}

	/**
	 * This function clears out all the filters currently set.
	 *
	 */
	public function clearFilters()
	{
		$this->filters = array();
	}

	/**
	 * This returns the unfiltered value of an element.
	 *
	 * @param string|int $offset
	 * @return mixed
	 */
	public function getUnfiltered($offset)
	{
		return $this[$offset];
	}

	/**
	 * This magic function overloads the array get handler to first filter the results.
	 *
	 * @param string|int $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		if(parent::offsetExists($offset))
			return $this->filterOutput(parent::offsetGet($offset));
	}

	/**
	 * This gets the iterator and passes it the filter functions that are applied to this array.
	 *
	 * @return FilteredArrayIterator
	 */
	public function getIterator()
	{
		$iterator = parent::getIterator();
		$iterator->setArrayReturnClass(get_class($this));
		$iterator->setFilters($this->filters);
		return $iterator;
	}

	/**
	 * This saves back the values and changes this class has made to the original array it was created from.
	 *
	 */
	public function save()
	{
		$this->original = $this->getArrayCopy();
	}

	/**
	 * This function is used to filter elements before sending them out.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
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

/**
 * This is an extention to the ArrayIterator class which applies filters to the output.
 *
 * @package Library
 * @subpackage Filters
 */
class FilteredArrayIterator extends ArrayIterator
{
	/**
	 * This is an array of filters that all the output gets sent through
	 *
	 * @var array
	 */
	protected $filters;

	/**
	 * This function takes an array of filters to apply to its output. Each time this function is called the previous
	 * filters get removed.
	 *
	 * @param array $filters
	 */
	public function setFilters($filters)
	{
		$this->filters = $filters;
	}

	/**
	 * This function overloads original to first filter the results.
	 *
	 * @return mixed
	 */
	public function current()
	{
		return $this->filterOutput(parent::current());
	}


	/**
	 * This function is used to filter elements before sending them out.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	protected function filterOutput($value)
	{
		if(is_array($value))
		{
			$temp =  new FilteredArray($value);
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