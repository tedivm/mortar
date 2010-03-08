<?php

class MortarControlRecentLocations extends ControlBase
{

	protected $name = "Recent Additions";

	protected $useLocation = true;

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
			while($row = $stmt->fetch_array()) {
				$loc = new Location($row['location_id']);
				$model = $loc->getResource();

				if(isset($model['title'])) {
					$name = $model['title'];
				} else {
					$name = str_replace('_', ' ', $loc->getName());
				}

				$url = new Url();
				$url->location = $loc->getId();
				$url->action = 'Read';
				$url->format = 'admin';

				$content .= '<b><a href="' . (string) $url . '">' . $name . '</a></b> ';
				$content .= '-- created on ' . date('d:m:y h:i a', strtotime($row['creationDate'])) . '<br />';
			}
		} else {
			$content = 'There are no additions at the specified location.';
		}

		return $content;
	}
}

?>