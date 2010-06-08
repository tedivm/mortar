<?php

class GraffitiPluginTemplateNavCategories
{

	public function hasTag($tagname)
	{
		if(strtolower($tagname) === 'categorylist')
			return true;

		return false;
	}

	public function categoryList($categoryName = null, $numEntries = 5)
	{
		if(!isset($categoryName))
			return false;

		$cat = ModelRegistry::loadModel('Category');
		if(!$cat->loadByName($categoryName))
			return false;

		$locs = GraffitiCategorizer::getCategoryLocations($cat->getId());
		$output = '<ul>';

		$x = 0;
		foreach($locs as $loc) {
			$output .= '<li><a href="' . $loc['url'] . '">' . $loc['name'] . '</a></li>';
			if($x++ >= $numEntries)
				break;
		}

		$output .= '</ul>';

		return $output;
	}
}

?>