<?php

class ChalkCoreActionBlogEntryComment extends ModelActionLocationBasedRead
{

	public function logic()
	{
		$location = $this->model->getLocation();
		$children = $location->getChildren();

		if(count($children) === 0)
			$this->message = "This entry does not have comments enabled.";
		
		foreach($children as $child) {
			if($child->getType() == 'Discussion') {
				$discussion = $child;
				break;
			}
		}

		if(isset($discussion)) {
			$query = Query::getQuery();

			$url = new Url();
			$url->locationId = $discussion->getId();
			$url->format = $query['format'];
			$this->ioHandler->addHeader('Location', (string) $url);
			$this->message = "Redirecting...";
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