<?php

class GraffitiActionTagInfo extends ActionBase
{
        static $requiredPermission = 'Read';

	public $adminSettings = array('headerTitle' => 'Tag Info', 'useRider' => true);
	public $htmlSettings =	array('headerTitle' => 'Tag Info', 'useRider' => true);

	protected $locationList;
	protected $owner = false;
	protected $tag = '';

	public function logic()
	{
		$query = Query::getQuery();

		if(isset($query['owner'])) {
			$this->owner = true;
		}

		if(isset($query['tag'])) {
			$this->tag = filter_var($query['tag'], FILTER_SANITIZE_STRING);
			$this->locationList = GraffitiTagLookUp::getLocationsForTag($query['tag'], $this->owner);
			$this->adminSettings['titleRider'] = ' For \'' . $this->tag . '\'';
			$this->htmlSettings['titleRider']  = ' For \'' . $this->tag . '\'';
		}
	}


	public function viewAdmin($page)
	{
		if(isset($this->locationList) && is_array($this->locationList) && count($this->locationList) >= 1) {
			$ul = new HtmlObject('ul');
			foreach($this->locationList as $locId) {
				$loc = new Location($locId);
				$model = $loc->getResource();
				$desig = isset($model['title']) ? $model['title'] : $loc->getName();
				$url = $model->getUrl();
				$link = $url->getLink($desig);
				$li = new HtmlObject('li');
				$li->wrapAround($link);
				$ul->wrapAround($li);
			}

			$note = '<p>Pages tagged \'' . $this->tag . '\'';
			if($this->owner)
				$note .= ' by author';

			$note .= ':</p>';

			return $note . $ul;
		} else {
			return 'This tag is not currently in use.';
		}
	}

	public function viewHtml($page)
	{
		return $this->viewAdmin($page);
	}
}

?>