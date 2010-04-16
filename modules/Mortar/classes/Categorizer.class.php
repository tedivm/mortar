<?php

class MortarCategorizer
{
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