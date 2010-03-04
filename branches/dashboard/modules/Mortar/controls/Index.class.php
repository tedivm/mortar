<?php

class MortarControlIndex extends ActionControl
{
	protected $useLocation = true;

	protected $name = "Index";

	protected $classes = array('two_wide');

	protected $customQuery = array('action' => 'Index');

	protected function setName()
	{
		if(isset($this->location)) {
			$loc = new Location($this->location);
			$model = $loc->getResource();

			if(isset($model['title'])) {
				$name = $model['title'];
			} else {
				$name = str_replace('_', ' ', $loc->getName());
			}

			$this->name .= ' of ' . $name;
		}

		return true;
	}
}

?>