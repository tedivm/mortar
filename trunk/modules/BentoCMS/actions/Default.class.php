<?php

class BentoCMSActionDefault extends Action
{
	protected $page;
	static $requiredPermission = 'Read';

	public function logic()
	{
		//$this->location;
		//$location = new Location();
		//$location = $this->location;

		$info = InfoRegistry::getInstance();
		$child = $this->location->getChildByName(str_replace('_', ' ', $info->Get['id']));

		if($child && $child->getResource() == 'page')
		{
			$this->page = new BentoCMSCmsPage($child->getId());
		}else{
			throw new ResourceNotFoundError();
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