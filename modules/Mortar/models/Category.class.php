<?php

class MortarModelCategory extends ModelBase
{
	static public $type = 'Category';
	protected $table = 'categories';

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
					FROM categories
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

	static function getCategoryTree()
	{
		$db = DatabaseConnection::getConnection('default_read_only');
		$results = $db->query('	SELECT categoryId, name
					FROM categories
					WHERE parent IS NULL
					ORDER BY name');
		$cats = array();

		while($row = $results->fetch_array()) {
			$item = array();
			$model = ModelRegistry::loadModel('Category', $row['categoryId']);
			$item['id'] = $row['categoryId'];
			$item['name'] = $row['name'];
			$item['children'] = $model->getDescendants();

			$cats[] = $item;
		}

		return $cats;
	}

	static function getDisplayTree()
	{
		$cats = self::getCategoryTree();
		return self::processTreeLevel($cats, 0);
	}

	static function processTreeLevel($cats, $level = 0)
	{
		$display = array();

		if(is_array($cats) && count($cats) === 0)
			return array();

		foreach($cats as $cat) {
			$item = array();
			$item['name'] = $cat['name'];
			$item['id'] = $cat['id'];
			$item['level'] = $level;
			$display[] = $item;

			$children = self::processTreeLevel($cat['children'], $level + 1);

			foreach($children as $item)
				$display[] = $item;
		}

		return $display;
	}
}

?>