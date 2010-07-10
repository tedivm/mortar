<?php

class TesseraPluginModelPostCommentAction
{
	public function getActions()
	{
		$pi = new PackageInfo('Tessera');
		$action = $pi->getActions('ModelPostComment');

		$action['name'] 		= 'PostComment';
		$action['outerName']		= 'Post Comment';

		return array('PostComment' => $action);
	}
}

?>