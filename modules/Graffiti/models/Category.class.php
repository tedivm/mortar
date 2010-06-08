<?php

class GraffitiModelCategory extends ModelBase
{
	static public $type = 'Category';
	protected $table = 'graffitiCategories';

	public function offsetSet($name, $value)
	{
		if($name == 'parent') {
			if($value == '' || $value == null) {
				$this->offsetUnset($name);
				return '';
			}

			if($this->hasAncestor($value)) {
				return false;
			}
		}

		return parent::offsetSet($name, $value);
	}

	public function hasAncestor($id)
	{
		if(!isset($this->id))
			return false;

		if($id === $this->id)
			return true;

		$cache = CacheControl::getCache('models', 'Category', $this->id, 'hasAncestor', $id);
		$return = $cache->getData();

		if($cache->isStale()) {
			$return = false;
			while (isset($id) && $id) {
				$cat = ModelRegistry::loadModel('Category', $id);
				$id = $cat['parent'];
				if($id === $this->id)
					$return = true;
			}
			$cache->storeData($return);
		}

		return $return;
	}

	public function getParent()
	{
		if(isset($this->content['parent'])) {
			return ModelRegistry::loadModel('Category', $this->content['parent']);
		} else {
			return false;
		}
	}

	public function getDescendants()
	{
		if(!isset($this->id))
			return array();

		$cache = CacheControl::getCache('models', 'Category', $this->id, 'getDescendants');
		$desc = $cache->getData();

		if($cache->isStale()) {
			$desc = array();

			$stmt = DatabaseConnection::getStatement('default_read_only');
			$stmt->prepare('SELECT *
					FROM graffitiCategories
					WHERE parent = ?');
			$stmt->bindAndExecute('i', $this->id);

			while($row = $stmt->fetch_array()) {
				$model = ModelRegistry::loadModel('Category', $row['categoryId']);
				$item = array();
				$item['id'] = $row['categoryId'];
				$item['name'] = $row['name'];
				$item['children'] = $model->getDescendants();

				$desc[] = $item;
			}

			$cache->storeData($desc);
		}

		return $desc;
	}

	/**
	 * Loads the category whose name is provided into this model
	 *
	 * @cache category lookup name *name id
	 * @static
	 * @param string $name
	 * @return int|false
	 */
	public function loadbyName($name)
	{
		$cache = CacheControl::getCache('category', 'lookup', 'name', $name, 'id');

		$id = $cache->getData();

		if($cache->isStale())
		{
			$db = DatabaseConnection::getConnection('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare('SELECT categoryId FROM graffitiCategories WHERE name = ?');
			$stmt->bindAndExecute('s', $name);

			if($stmt->num_rows == 1)
			{
				$results = $stmt->fetch_array();
				$id = $results['categoryId'];
			}else{
				$id = false;
			}
			$cache->storeData($id);
		}
		return $this->load($id);
	}

	public function __toArray()
	{
		$array = parent::__toArray();
		$locs = GraffitiCategorizer::getCategoryLocations($this->id);
		$array['locations'] = $locs;
		return $array;
	}

	public function save()
	{
		CacheControl::clearCache('models', 'Category');
		return parent::save();
	}
}

?>