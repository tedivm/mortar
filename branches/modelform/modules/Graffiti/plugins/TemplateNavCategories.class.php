<?php

class GraffitiPluginTemplateNavCategories
{

	public function hasTag($tagname)
	{
		if(strtolower($tagname) === 'categoryentries')
			return true;

		return false;
	}

	public function getTag($tagname, $id)
	{
		if($tagname !== 'categoryentries')
			return false;

		if(!isset($id))
			return false;

		$locs = GraffitiCategorizer::getCategoryLocations($id);
		$output = '<ol>';

		foreach($locs as $loc) {
			$output .= '<li><a href="' . $loc['url'] . '">' . $loc['name'] . '</a></li>';
		}

		$output .= '</ol>';

		return $output;
	}
}

?>