<?php

class MortarModelCategory extends ModelBase
{
	static public $type = 'Category';
	protected $table = 'categories';

	public function offsetSet($name, $value)
	{
		if($name == 'parent') {
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
}

?>