<?php

abstract class ActionControl extends ControlBase
{
	protected $action;
	protected $customQuery = null;

	public function getContent()
	{
		$iop = new IOProcessorHttp();
		$name = $this->action;
		$argument = '';

		if($this->useLocation && $this->location)
		{
			$loc = new Location($this->location);
			$argument = $loc->getResource();
		}

		if(isset($this->customQuery)) {
			$oldquery = Query::getQuery();
			$query = Query::getQuery();
			foreach($this->customQuery as $key => $value) {
				$query[$key] = $value;
			}

			Query::setQuery($query);
		}

		if ($this->useLocation === false || ($this->useLocation && $this->location)) {
			$action = new $name($argument, $iop);
			$page = ActivePage::getInstance();
			$action->logic();
			$result = $action->viewAdmin($page);

			if(isset($oldquery)) {
				Query::setQuery($oldquery);
			}

			return $result;
		} else {
			return "This control requires a location to be set.";
		}
	}
}

?>