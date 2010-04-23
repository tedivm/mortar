<?php

class TagBoxNav
{

	protected $location;

	public function __construct(Location $location)
	{
		$this->location = $location;
	}

	protected function navList($children, $marker = true)
	{
		if(!($children)) return false;

		$childList = new HtmlObject('ul');
		$childList->addClass('navList');

		$cacheKey = '';
		foreach($children as $child)
			$cacheKey .= $child->getId() . "_";

		$cache = CacheControl::getCache('tagboxes', $this->location->getId(), 'nav', 'navList', $cacheKey);

		$navList = $cache->getData();

		if ($cache->isStale()) {
			foreach($children as $child) {

				$url = new Url();
				$url->location = $child->getId();
				$url->format = 'html';

				$model = $child->getResource();

				$childLink = new HtmlObject('a');
				$childLink->property('href', (string) $url)->
					addClass('navLink');

				$childItem = new HtmlObject('li');
				$childItem->addClass('navItem');

				$childName = isset($model['title'])
					? $model['title']
					: (isset($model->name)
						? ucwords(str_replace('_', ' ', $model->name))
						: '');

				if(isset($childName) && ($marker) && ($child->getId() === $this->location->getId()))
					$childItem->wrapAround($childName)->
						addClass('navItemSelect');
				else
					$childItem->wrapAround($childLink->wrapAround($childName));

				$childList->wrapAround($childItem);
			}

			$navList = (string) $childList;
			$cache->storeData($navList);
		}

		return $navList;
	}

	protected function siblingNav($marker = true)
	{
		$children = $this->location->getParent()->getChildren();

		$navList = $this->navList($children, $marker);

		return (string) $navList;
	}

	protected function childrenNav()
	{
		$children = $this->location->getChildren();

		$navList = $this->navList($children);

		return (string) $navList;
	}

	protected function convertLoc($loc)
	{
		if(!isset($loc)) {
			$loc = $this->location->getId();
		} elseif(!is_numeric($loc)) {
			$site = ActiveSite::getSite();
			$siteloc = $site->getLocation();
			$siteid = $siteloc->getId();

			$l = Location::getIdByPath($loc, $siteid);

			if($l === false) {
				return false;
			} else {
				$loc = $l;
			}
		}

		return $loc;
	}

	public function pagelist($loc = null, $class = 'pagelist') {
		if(!($loc = $this->convertLoc($loc)))
			return false;

		$location = new Location($loc);
		$children = $location->getChildren();
		$navList = $this->navList($children);

		$navList->addClass($class);

		return (string) $navList;
	}

	public function monthArchive($loc = null, $countItems = true, $class = 'monthArchive')
	{
		if(!($loc = $this->convertLoc($loc)))
			return false;

		$cache = CacheControl::getCache('tagboxes', $loc, 'nav', 'navByMonth');

		$data = $cache->getData();

		if($cache->isStale())
		{
			$data = array();

			$db = db_connect('default_read_only');
			$stmt = $db->stmt_init();

			$stmt->prepare('SELECT DISTINCT MONTH(publishDate) as month, 
						YEAR(publishDate) as year, 
						COUNT(publishDate) as count
					FROM locations
					WHERE parent = ?
					GROUP BY month, year
					ORDER BY Year, Month');

			$stmt->bindAndExecute('i', $loc);

			$dateList = new HtmlObject('ul');
			$dateList->addClass('dateList');

			while($row = $stmt->fetch_array()) {
				$data[] = $row;
			}

			$cache->storeData($data);
		}
			
		foreach($data as $monthYear) {
			$month = $monthYear['month'];
			$year = $monthYear['year'];

			$formattedDate = date('F Y', strtotime($month . '/01/' . $year));

			$url = new Url();
			$url->location = $this->location->getId();
			$url->property('month', $month);
			$url->property('year', $year);

			$dateLink = new HtmlObject('a');
			$dateLink->property('href', (string) $url)->
				wrapAround($formattedDate);

			$dateItem = new HtmlObject('li');
			$dateItem->addClass('dateItem')->
				wrapAround($dateLink);

			if($countItems) {
				$count = $monthYear['count'];
				$dateItem->wrapAround(' (' . $count . ')');
			}

			$dateList->wrapAround($dateItem);
		}

		$dateList->addClass($class);

		return (string) $dateList;
	}

	public function __call($tagname, $args)
	{
		$hook = new Hook();
		$hook->loadPlugins('Template', 'Navigation', 'Tags');
		$results = Hook::mergeResults($hook->hasTag($tagname));

		if($results === true || in_array(true, $results)) {
			return Hook::mergeResults(call_user_func_array(array($hook, 'getTag'), $args));
		} else {
			return null;
		}
		
	}

	public function __get($tagname)
	{
		$hook = new Hook();
		$hook->loadPlugins('Template', 'Navigation', 'Tags');
		$results = Hook::mergeResults($hook->hasTag($tagname));

		if($results === true || in_array(true, $results))
			return Hook::mergeResults($hook->getTag($tagname));

		switch ($tagname) {
			case "siblingList":
				return $this->siblingNav();
			case "childrenList":
				return $this->childrenNav();
			default:
				return false;
		}
	}

	public function __isset($tagname)
	{
		$hook = new Hook();
		$hook->loadPlugins('Template', 'Navigation', 'Tags');
		$results = Hook::mergeResults($hook->hasTag($tagname));

		if($results === true || in_array(true, $results))
			return true;		

		switch ($tagname) {
			case "siblingList":
			case "childrenList":
			default:
				return false;
		}
	}
}

?>
