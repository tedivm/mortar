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
 * This class is used to collect a list of related models for use in index and other classes based off of the models'
 * location.
 *
 * @package System
 * @subpackage ModelSupport
 */
class LocationListing extends ModelListing
{
	/**
	 * Defines a list of fields for which a join table and key are provided to be used when sorting by said field.
	 *
	 * @var array
	 */
	protected $sortJoins = array('owner' => array('users', 'primarykey', 'name'), 'groupOwner' => array('memberGroup', 'primarykey', 'name'));

	/**
	 * This array defines which columns map to what value (type or id). Because the ORM class is used the string
	 * "primarykey" will map to whatever the primary key of the table is.
	 *
	 * @var array
	 */
	protected $lookupColumns = array('resourceType' => 'type', 'resourceId' => 'id', 'resourceStatus' => 'status',
					 'creationDate' => 'createdOn');

	/**
	 * This contains the name of the table being used to retrieve the models.
	 *
	 * @var string
	 */
	protected $table = 'locations';

	/**
	 * All locations are listed using the same table.
	 *
	 */
	public function __construct()
	{
		$this->tableStructure = new OrmTableStructure($this->table, 'default_read_only');
	}

	/**
	 * This function sets a requirement for the models being retrieved. For this implementation the names are mapped
	 * directly to a database column, and the value sets what that column needs to be. For three specific
	 * location factors, convenient aliases are provided.
	 *
	 * @param string $name
	 * @param string|int $value
	 */
	public function addRestriction($name, $value)
	{
		switch($name)
		{
			case 'type':
				$name = 'resourceType';
				break;
			case 'id':
				$name = 'resourceId';
				break;
			case 'date':
				$name = 'publishDate';
		}

		parent::addRestriction($name, $value);
	}

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

	/**
	 * This function filters the retrieved models by permission, testing against the active user.
	 * This makes use of the checkListPermissions method in order to minimize the database queries
	 * needed to check a list of Locations.
	 *
	 * @param array $modelArray
	 * @return array
	 */
	protected function filterModels($modelArray)
	{
		$user = ActiveUser::getUser();
		$filteredModels = array();
		$locs = array();

		foreach($modelArray as $modelInfo) {
			$model = ModelRegistry::loadModel($modelInfo['type'], $modelInfo['id']);
			$locs[$modelInfo['id']] = $model->getLocation();
		}

		$results = Permissions::checkListPermissions($locs, $user, 'Read');

		foreach($modelArray as $modelInfo) {
			$loc = $locs[$modelInfo['id']];
			if($results[$loc->getId()] === true) {
				$filteredModels[] = $modelInfo;
			}
		}

		return $filteredModels;
	}
}

?>