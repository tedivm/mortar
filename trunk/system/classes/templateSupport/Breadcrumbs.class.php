<?php

class TagBoxBreadcrumbs
{
	protected $location;
	protected $user;
	protected $query;

	public function __construct($location)
	{
		if(defined('INSTALLMODE') && INSTALLMODE === true)
			return;

		$this->location = $location;
		$this->user = ActiveUser::getUser();
		$this->query = Query::getQuery();
	}

	public function getCrumbs($sep = '', $html = true, $rev = false, $stopId = null)
	{
		if(defined('INSTALLMODE') && INSTALLMODE === true) {
			if($html) {
				return '';
			} else {
				return 'Install Mortar';
			}
		}

		$nameList = array();

		$userId = $this->user->getId();

		$location = $this->location;
		$urlList = array();
		$x = 1;

		$page = ActivePage::getInstance();
		$pagetitle = $page->getTitle();

		// First, check whether a non-read action is being performed. If so, add that action to the front of the list.

		if(isset($this->query['action']) && $this->query['action'] !== 'Read' && $pagetitle !== 'Read') {
			if($html) {
				$url = Query::getUrl();
				$nameList[] = $url->getLink($pagetitle);
			} else {
				$nameList[] = $pagetitle;
			}
		}

		// Then, check whether a location is set.

		if( !isset($this->query['location']) ) {

			// If one isn't, check whether a type and id are set. 
			// If so, we're looking at a non-LB model; add it to the front of the list.

			if( isset($this->query['id']) && isset($this->query['type']) ) {
				$model = ModelRegistry::loadModel($this->query['type'], $this->query['id']);
				$title = $model->getDesignation();

				if($html) {
					$url = Query::getUrl();
					$url->action = 'Read';
					$nameList[] = $url->getLink($title);
				} else {
					$nameList[] = $title;
				}
			}

			// Then, check whether a type is set. 
			// If so, we're looking at non-LB models and the link to the index of 
			// the type should be added to the list. 
			// If not, we're in an error state and the error message title should
			// go on the list.

			if(isset($this->query['type'])) {
				if($html) {
					$url = Query::getUrl();
					$url->type = $this->query['type'];
					$url->action = 'Index';
					unset($url->id);
					$nameList[] = $url->getLink($this->query['type']);
				} else {
					$nameList[] = $this->query['type'];
				}
			} else {
				if($html) {
					$url = Query::getUrl();
					$nameList[] = $url->getLink($pagetitle);
				} else {
					$nameList[] = $pagetitle;
				}
			}
		}

		// If nothing is on the list yet, we are viewing a location. We check to see if the page title 
		// matches the title of the first location on the list; if not, we add the page title to the list.

		if(!isset($nameList) || count($nameList) === 0) {
			$model = $location->getResource();
			$name = $model->getDesignation();
			if($pagetitle !== $name && $pagetitle !== 'Read') {
				if($html) {
					$url = Query::getUrl();
					$nameList[] = $url->getLink($pagetitle);
				} else {
					$nameList[] = $pagetitle;
				}
			}
		}

		// If a stop location is set, we need to initialize it here.
		if(isset($stopId)) {
			$endLoc = Location::getLocation($stopId);
			$stopLoc = $endLoc->getParent();
		}


		// Finally, we loop through the list of locations. If we have a non-location-based
		// resource, this list will only contain the active site; otherwise, we start at the
		// current location and go back through its parents until we reach the root, adding
		// each to the list.

		do {
			if($location->getType() == 'Root')
				break;

			if(isset($stopLoc) && $location->getId() === $stopLoc->getId())
				break;

			$url = new Url();
			$url->location = $location->getId();
			$url->format = $this->query['format'];

			if($url->checkPermission($userId) && $location->checkPublished()) {
				$model = $location->getResource();
				$name = $model->getDesignation();

				if(isset($firstname)) {
					$name = $firstname;
					unset($firstname);
				}

				if($html) {
					$nameList[] = $url->getLink($name);
				} else {
					$nameList[] = $name;
				}
			}
		} while($location = $location->getParent());

		// if $rev is set we flip the array into reverse order

		if(!$rev) {
			$nameList = array_reverse($nameList);
		}

		// if $html is set we transform the list into an HTML-wrapped affair, otherwise we
		// intersperse dividers between the text entries

		if($html) {
			$breadCrumb = new HtmlObject('div');
			$breadCrumb->property('id', count($urlList)."_level_breadcrumbs");
			$breadCrumb->addClass('breadcrumbs');

			$breadCrumbList = new HtmlObject('ul');
			$breadCrumbList->addClass('breadcrumblist');
			$breadCrumb->wrapAround($breadCrumbList);

			$first = true;

			foreach($nameList as $url) {

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

	public function startFrom($stopLoc)
	{
		$site = ActiveSite::getSite();
		$siteLoc = $site->getLocation();
		$siteId = $siteLoc->getId();

		$locId = Location::getIdByPath($stopLoc, $siteId);
		if(isset($locId) && $locId) {
			return $this->getCrumbs('', true, false, $locId);
		} else {
			return false;
		}
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