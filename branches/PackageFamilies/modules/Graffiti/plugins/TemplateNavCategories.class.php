<?php

class GraffitiPluginTemplateNavCategories
{

	public function hasTag($tagname)
	{
		switch(strtolower($tagname)) {
			case 'categorylist':
			case 'tagcloud':
				return true;
			default:
				return false;
		}
	}

	public function tagCloud($size = .5)
	{
		$tags = GraffitiTagLookUp::getTagList();

		$query = Query::getQuery();
		$baseUrl = new Url();
		$baseUrl->module = 'Graffiti';
		$baseUrl->action = 'TagInfo';
		$baseUrl->format = $query['format'];

		$output = new HtmlObject('div');
		$output->addClass('tag-cloud');

		foreach($tags as $tag) {
			$ems = $size * ($tag['weight']);

			$url = clone($baseUrl);
			$url->tag = $tag['tag'];
			$link = $url->getLink($tag['tag']);

			$tagSpan = new HtmlObject('span');
			$tagSpan->addClass('tag');
			$tagSpan->property('style', 'font-size: ' . $ems . 'em');
			$tagSpan->wrapAround($link);
			$output->wrapAround($tagSpan);
		}

		return (string) $output;
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