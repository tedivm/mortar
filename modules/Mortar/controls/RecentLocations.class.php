<?php

class MortarControlRecentLocations extends ControlBase
{
	protected $name = "Recent Additions";

	protected $useLocation = true;

	protected $classes = array('two_wide');

	public function getContent()
	{
		$content = '';

		$db = DatabaseConnection::getConnection('default_read_only');
		$stmt = $db->stmt_init();
		$stmt->prepare('SELECT location_id, creationDate 
				FROM locations 
				WHERE parent = ?
				ORDER BY creationDate DESC 
				LIMIT 5');
		$success = $stmt->bindAndExecute('i', $this->location);

		if($success) {
			$loc = new Location($this->location);
			$mmodel = $loc->getResource();

			$models = array();
			while($row = $stmt->fetch_array()) {
				$loc = new Location($row['location_id']);
				$model = $loc->getResource();

				$models[] = $model;
			}

			$indexListing = new ViewTableDisplayList($mmodel, $models, array('name' => 'Name', 
				'title' => 'Title', 'createdOn' => 'Created On'));

			$indexListing->useIndex(false);
			$indexListing->addPage(ActivePage::getInstance());

			return $indexListing->getListing();
		} else {
			$content = 'There are no recent additions at the specified location.';
		}

		return $content;
	}

	protected function setName()
	{
		if(isset($this->location)) {
			$loc = new Location($this->location);
			$model = $loc->getResource();

			if(isset($model['title'])) {
				$name = $model['title'];
			} else {
				$name = str_replace('_', ' ', $model->name);
			}

			$this->name .= ' at ' . $name;
		}
	}

}

?>