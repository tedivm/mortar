<?php

class ChalkActionBlogEntryComment extends ModelActionLocationBasedRead
{

	public function logic()
	{
		$children = $this->model->getChildren();

		if(count($children) === 0)
			$this->message = "This entry does not have comments enabled.";
		
		foreach($children as $child) {
			if($child->getType() == 'Discussion') {
				$discussion = $child;
				break 2;
			}
		}

		if(isset($discussion)) {
			$location = $discussion->getLocation();
			$query = Query::getQuery();

			$url = new Url();
			$url->locationId = $location->getId();
			$url->format = $query['format'];
			$this->ioHandler->addHeader('Location', (string) $url);
		} else {
			$this->message = "This entry does not have comments enabled.";
		}
	}

	public function viewAdmin($page)
	{
		return $this->message;
	}

	public function viewHtml($page)
	{
		return $this->message;
	}
}

?>