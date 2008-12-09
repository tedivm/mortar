<?php

class BentoCMSPageList
{

	protected $modId;

	public function __construct($id)
	{
		if(is_numeric($id))
			$this->modId = $id;
	}

	public function getPages($status = false)
	{
		$db = dbConnect('default_read_only');
		$listPages = $db->stmt_init();

		$listPages->prepare('SELECT pageId, pageName, pageStatus FROM cmsPages WHERE mod_id = ?');
		$listPages->bind_param_and_execute('i', $this->modId);

		$pageList = array();
		while($row = $listPages->fetch_array())
		{
			if(is_array($status) && !in_array($row['pageStatus'], $status))
				continue;

			$pageList[$row['pageName']] = new BentoCMSCmsPage($row['pageId']);
		}

		return $pageList;
	}

}


?>