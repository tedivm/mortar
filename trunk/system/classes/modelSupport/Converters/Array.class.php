<?php

class ModelToArray
{
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