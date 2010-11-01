<?php

class GraffitiPluginModelTagAction
{
	public function getActions()
	{
		$pi = PackageInfo::loadByName(null, 'Graffiti');
		$action = $pi->getActions('ModelTag');

		$action['name'] = 'Tag';
		$action['outerName'] = 'Tag';

		return array('Tag' => $action);
	}
}

?>