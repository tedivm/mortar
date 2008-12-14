<?php

class BentoCMSActionViewPage extends Action
{
	protected $page;
	static $requiredPermission = 'Read';

	protected $resourceType = 'Page';

	public function logic()
	{
		$info = InfoRegistry::getInstance();
		$child = $this->location->getChildByName(str_replace('_', ' ', $info->Get['id']));

		if($child && $child->getResource() == $this->resourceType)
		{
			$this->page = new BentoCMSCmsPage($child->getId());
		}else{
			throw new ResourceNotFoundError();
		}

	}

	protected function htmlContentArea()
	{
		$revision = $this->page->getRevision();
		return $revision->property('content');
	}

	public function viewHtml()
	{
		$page = ActivePage::getInstance();
		$revision = $this->page->getRevision();
		$status = $this->page->property('status');

		switch (true){

			case ($status == 'draft' && $this->checkAuth('Edit')):
				break;

			case ($status == 'deleted' && $this->checkAuth('Add')):
				$title = '*DELETED* ';
				break;

			case ($status == 'active'):
				break;

			default:
				throw new ResourceNotFoundError();
				break;
		}




//var_dump($revision->property('content'));

		$page->addRegion('main_content', $this->htmlContentArea());

//var_dump($page);

		$page->addRegion('title', $revision->property('title'));
		$page->addMeta('keywords', $this->page->property('keywords'));
		$page->addMeta('description', $this->page->property('description'));


		//return true;
	}
}

?>