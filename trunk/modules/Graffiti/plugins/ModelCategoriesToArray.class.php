<?php

class GraffitiPluginModelCategoriesToArray
{

	public function toArray(Model $model)
	{
		if(!method_exists($model, 'getLocation'))
			return array();

		$loc = $model->getLocation();

		$array = array();
		$catInfo = array();

		$cats = GraffitiCategorizer::getLocationCategories($loc, true);

		foreach($cats as $id) {
			$cat = ModelRegistry::loadModel('Category', $id);
			$catInfo[$cat['name']] = array(	'name' => $cat['name'], 
							'id' => $cat->getId(),
							'url' => (string) $cat->getUrl());
		}

		$array['categories'] = $catInfo;

		return $array;
	}
}

?>