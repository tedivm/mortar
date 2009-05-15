<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage ModelSupport
 */

/**
 * This class returns an array representation of the model
 *
 * @package System
 * @subpackage ModelSupport
 */
class ModelToArray
{
	/**
	 * This function converts a model into an array
	 *
	 * @static
	 * @param Model $model
	 * @return array
	 */
	static function convert($model)
	{
		$finalArray = array();
		$properties = $model->getProperties();

		foreach($properties as $index => $property)
		{
			$propertyName = $property->getName();
			$node = $this->$name;

			if(is_scalar($node))
			{
				$finalArray[$name] = $node;
			}elseif(is_array()){
				// do we need to descend and sanitize this thing?
				$finalArray[$name] = $node;
			}elseif($node instanceof Model){
				$finalArray[$name] = $node->__toArray();
			}
		}

		$attributes = $model->getAttributes();

		foreach($properties as $index => $property)
		{
			$propertyName = $property->getName();
			$node = $this->$name;

			if(is_scalar($node))
			{
				$finalArray['attributes'][$name] = $node;
			}
		}

		return $finalArray;
	}

}