<?php

class GraffitiPluginModelTagAction
{
	public function getActions()
	{
		$pi = new PackageInfo('Graffiti');
		$action = $pi->getActions('ModelTag');

		$action['name'] 		= 'Tag';
		$action['outerName']		= 'Tag';

		return array('Tag' => $action);
	}
}

?>