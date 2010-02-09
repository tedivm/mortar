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

				$childName = $model['title'];

				if(($marker) && ($child->getId() === $this->location->getId()))
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

	protected function navByMonth()
	{
		$cache = CacheControl::getCache('tagboxes', $this->location->getId(), 'nav', 'navByMonth');

		$dateList = $cache->getData();

		if($cache->isStale())
		{
			$db = db_connect('default_read_only');
			$stmt = $db->stmt_init();

			$sql  = 'SELECT DISTINCT MONTH(publishDate) as month, YEAR(publishDate) as year ';
			$sql .= 'FROM locations WHERE parent = ? ORDER BY Year, Month';

			$stmt->prepare($sql);
			$stmt->bindAndExecute('i', $this->location->getId());

			$dateList = new HtmlObject('ul');
			$dateList->addClass('dateList');

			while($monthYear = $stmt->fetch_array())
			{
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

				$dateList->wrapAround($dateItem);
			}

			$cache->storeData($dateList);
		}

		return (string) $dateList;
	}

	public function __get($tagname)
	{
		switch ($tagname) {
			case "siblingList":
				return $this->siblingNav();
			case "childrenList":
				return $this->childrenNav();
			case "monthArchive":
				return $this->navByMonth();
			default:
				return false;
		}
	}

	public function __isset($tagname)
	{
		switch ($tagname) {
			case "siblingList":
			case "childrenList":
			case "monthArchive":
				return true;
			default:
				return false;
		}
	}
}

?>
