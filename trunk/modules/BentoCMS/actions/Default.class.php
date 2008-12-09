<?php

class BentoCMSActionDefault extends Action
{
	protected $page;

	public function logic()
	{
		$info = InfoRegistry::getInstance();
		$id = $info->Get['id'];

		$db = dbConnect('default_read_only');
		$pageStmt = $db->stmt_init();
		$pageStmt->prepare('SELECT pageId FROM cmsPages WHERE mod_id = ? AND pageName = ?');
		$pageStmt->bind_param_and_execute('is', $this->moduleId, $id);

		if($pageStmt->num_rows != 1)
		{
			throw new ResourceNotFoundError();
		}else{


			$row = $pageStmt->fetch_array();

			$cmsPage = new BentoCMSCmsPage($row['pageId']);
			$this->page = $cmsPage;
		}
	}

	public function viewHtml()
	{
		$revision = $this->page->getRevision();
		$status = $this->page->property('status');

		switch (true){

			case ($status == 'draft' && $this->checkAuth('Edit')):
				$revision->property($revision->property('title', '*DRAFT* ' . $revision->property('title')));
				break;

			case ($status == 'deleted' && $this->checkAuth('Add')):
				$revision->property($revision->property('title', '*DELETED* ' . $revision->property('title')));
				break;

			case ($status == 'active'):
				break;

			default:
				throw new ResourceNotFoundError();
				break;
		}


		$this->page->sendToActivePage($revision);


	}
}

?>