<?php

class BentoCMSPageList
{
	
	protected $modId;
	
	public function __construct($id)
	{
		if(is_numeric($id))
			$this->modId = $id;
	}
	
	public function getPages()
	{
		$db = dbConnect('default_read_only');
		$listPages = $db->stmt_init();
		
		$listPages->prepare('SELECT pageId, pageName FROM cmsPages WHERE mod_id = ?');
		$listPages->bind_param_and_execute('i', $this->modId);
		
		$pageList = array();
		while($row = $listPages->fetch_array())
		{
			$pageList[$row['pageName']] = new BentoCMSCmsPage($row['pageId']);
		}
		
		return $pageList;
	}
	
}


?>