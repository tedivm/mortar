<?php
/**
 * Mortar
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
	protected $model;

	public function __construct(Model $model)
	{
		$this->model = $model;
	}

	/**
	 * This function converts a model into an array
	 *
	 * @static
	 * @param Model $model
	 * @return array
	 */
	public function getOutput()
	{
		return $this->model->__toArray();
	}

}