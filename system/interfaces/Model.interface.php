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
 * All models should follow this interface. It inherits the ArrayAccess interface.
 *
 * @package System
 * @subpackage ModelSupport
 */
Interface Model extends ArrayAccess
{
	/**
	 * The id is passsed to the model to load it from storage.
	 *
	 * @param mixed $id
	 */
	public function __construct($id = null);

	/**
	 * This function saves the model to the database. It takes an optional argument of a Location, which if passed
	 * is where the model should be saved.
	 *
	 */
	public function save();

	/**
	 * This function should delete the stored verion of the mode.
	 *
	 * @return bool
	 */
	public function delete();

	/**
	 * This function takes in the action and checks to see if the user if allowed to perform it on the model. If the
	 * user isn't passed, the current active user should be checked.
	 *
	 * @param string $action
	 * @param User $user
	 * @return bool
	 */
	public function checkAuth($action, $user = null);

	/**
	 * This function takes in a string, as an action type, and returns information about the action class used to
	 * perform that action.
	 *
	 * @param string $actionName
	 * @return array Defined indexes are 'classname' and 'path'
	 */
	public function getAction($actionName);

	/**
	 * Returns all of the content of a model, as an array.
	 *
	 * @return array
	 */
	public function getContent();

	/**
	 * Returns the ID for the model.
	 *
	 * @return int|mixed
	 */
	public function getId();

	/**
	 * This returns all the properties of the model as an array.
	 *
	 * @return array
	 */
	public function getProperties();

	/**
	 * This returns a string representing the type of model this is.
	 *
	 * @return string
	 */
	public function getType();

	/**
	 * This returns a string naming the module that this model is packaged with.
	 *
	 * @return string
	 */
	public function getModule();


	/**
	 * Returns a property of the model.
	 *
	 * @param string $offset
	 * @return mixed
	 */
	public function __get($offset);

	/**
	 * Sets a property of the model.
	 *
	 * @param string|int $offset
	 * @param string|mixed $value
	 */
	public function __set($offset, $value);

	/**
	 * Checks to see if a property is set.
	 *
	 * @param string|int $offset
	 * @return bool
	 */
	public function __isset($offset);

	/**
	 * Unsets a property of the model.
	 *
	 * @param string|int $offset
	 */
	public function __unset($offset);

	/**
	 * Returns an array representation of the model.
	 *
	 * @return array
	 */
	public function __toArray();

}

?>