<?php

class TesseraPluginModelPostCommentAction
{
	public function getActions()
	{
		$pi = PackageInfo::loadByName(null, 'Tessera');
		$action = $pi->getActions('ModelPostComment');

		$action['name'] 		= 'PostComment';
		$action['outerName']		= 'Post Comment';

		return array('PostComment' => $action);
	}
}

?>