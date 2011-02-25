<?php

class TesseraCorePluginModelPostCommentAction
{
	public function getActions()
	{
		$pi = PackageInfo::loadByName('Tessera', 'Core');
		$action = $pi->getActions('ModelPostComment');

		$action['name'] 		= 'PostComment';
		$action['outerName']		= 'Post Comment';

		return array('PostComment' => $action);
	}
}

?>