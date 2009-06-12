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
 * This is the base class for all the other model based actions. It handles loading various information, checking
 * permissions, and setting cache headers based on the models age.
 *
 * @abstract
 * @package System
 * @subpackage ModelSupport
 */
abstract class ModelActionLocationBasedBase extends ModelActionBase
{

	/**
	 * This function sends along the Last-Modified headers, and if $this->cacheExpirationOffset is set it also sends
	 * that to the ioHandler. This is vital for client side http caching
	 *
	 * @access protected
	 */
	protected function setHeaders()
	{
		$location = $this->model->getLocation();
		$modifiedDate = strtotime($location->getLastModified());
		$this->ioHandler->addHeader('Last-Modified', gmdate('D, d M y H:i:s T', $modifiedDate));

		if(isset($this->cacheExpirationOffset) && !isset($this->ioHandler->cacheExpirationOffset))
			$this->ioHandler->cacheExpirationOffset = $this->cacheExpirationOffset;

	}


	/**
	 * This creates the permission object and saves it. This function can be overwritten for special purposes, such as
	 * with the Add class which needs to check the parent models permission, not the current model.
	 *
	 */
	protected function setPermissionObject()
	{
		$user = ActiveUser::getUser();
		$this->permissionObject = new Permissions($this->model->getLocation(), $user);
	}

	/*
	public function viewAdmin()
	{

	}

	public function viewHtml()
	{

	}

	public function viewXml()
	{

	}

	public function viewJson()
	{

	}
	*/
}

?>