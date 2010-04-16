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

	protected function hasAncestor($id)
	{
		if(!isset($this->id))
			return false;

		while (isset($id) && $id) {
			$cat = ModelRegistry::loadModel('Category', $id);
			$id = $cat->getId();
			if($id === $this->id)
				return true;
		}

		return false;
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