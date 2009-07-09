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
 * This class is used to collect a list of related models for use in index and other classes based off of the models'
 * location.
 *
 * @package System
 * @subpackage ModelSupport
 */
class LocationListing extends ModelListing
{
	/**
	 * This array defines which columns map to what value (type or id). Because the ORM class is used the string
	 * "primarykey" will map to whatever the primary key of the table is.
	 *
	 * @var array
	 */
	protected $lookupColumns = array('resourceType' => 'type', 'resourceId' => 'id');

	/**
	 * This contains the name of the table being used to retrieve the models.
	 *
	 * @var string
	 */
	protected $table = 'locations';

	/**
	 * This function returns the base array used to distinguish this cache item from others. This is just a base- the
	 * options and restrictions get added seperately.
	 *
	 * @return array
	 */
	protected function getCacheArray()
	{
		if(!isset($this->restrictions['parent']))
			return false;

		$cacheKey = array('locations', $this->restrictions['parent']);
		return $cacheKey;
	}




}

?>