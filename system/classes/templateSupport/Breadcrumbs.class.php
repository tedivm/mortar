<?php

class TagBoxBreadcrumbs
{
	protected $location;
	protected $user;
	protected $query;

	public function __construct($location)
	{
		$this->location = $location;
		$this->user = ActiveUser::getUser();
		$this->query = Query::getQuery();
	}

	public function getCrumbs($sep = '', $html = true, $rev = false)
	{
		$userId = $this->user->getId();

		$location = $this->location;
		$urlList = array();
		$x = 1;

		if(isset($this->query['action']) && $this->query['action'] !== 'Read') {
			$page = ActivePage::getInstance();
			$title = $page->getTitle();

			if($html) {
				$url = Query::getUrl();
				$nameList[] = $url->getLink($title);
			} else {
				$nameList[] = $title;
			}
		}

		if( ($location->getType() === 'Site') && isset($this->query['type']) && 
			($this->query['type'] !== 'Site') ) {

			if(isset($this->query['id'])) {
				$page = ActivePage::getInstance();
				$title = $page->getTitle();

				if($html) {
					$url = Query::getUrl();
					$nameList[] = $url->getLink($title);
				} else {
					$nameList[] = $title;
				}
			}

			if($html) {
				$url = new Url();
				$url->type = $this->query['type'];
				$nameList[] = $url->getLink($this->query['type']);
			} else {
				$nameList[] = $this->query['type'];
			}
		}

		do {
			if($location->getType() == 'Root')
				break;

			$url = new Url();
			$url->location = $location->getId();
			$url->format = $this->query['format'];

			if($url->checkPermission($userId)) {
				$model = $location->getResource();
				$name = isset($model['title']) ? $model['title'] : str_replace('_', ' ', $location->getName());

				if($html)
					$nameList[] = $url->getLink($name);
				else
					$nameList[] = $name;
			}
		} while($location = $location->getParent());

		if(!$rev) {
			$nameList = array_reverse($nameList);
		}

		if($html) {
			$breadCrumb = new HtmlObject('div');
			$breadCrumb->property('id', count($urlList)."_level_breadcrumbs");
			$breadCrumb->addClass('breadcrumbs');

			$breadCrumbList = new HtmlObject('ul');
			$breadCrumbList->addClass('breadcrumblist');
			$breadCrumb->wrapAround($breadCrumbList);

			$first = true;

			foreach($nameList as $url)
			{
				if($first) {
					$first = false;
				} elseif ($sep !== '') {
					$separator = new HtmlObject('div');
					$separator->addClass('breadcrumb_separator');
					$separator->wrapAround(" $sep ");
					$breadCrumb->wrapAround($separator);
				}

				$listItem = $breadCrumbList->insertNewHtmlObject('li');
				$listItem->wrapAround($url);
			}

			$listItem->addClass('current');
		} else {
			$breadCrumb = '';
			$first = true;

			foreach($nameList as $name) {
				if($first)
					$first = false;
				else
					$breadCrumb .= " $sep ";
				$breadCrumb .= $name;
			}
		}

		return (string) $breadCrumb;
	}

	public function __toString()
	{
		return $this->getCrumbs();
	}

	public function __get($key)
	{
		switch($key) {
			case 'crumbs':
				return $this->getCrumbs();
			case 'titlecrumbs':
				return $this->getCrumbs('|', false, true);
		}
	}

	public function __isset($key)
	{
		switch($key) {
			case 'crumbs':
			case 'titlecrumbs':
				return true;
			default:
				return false;	
		}
	}

}
?>