<?php

class BentoCMSActionListPages extends PackageAction
{
	static $requiredPermission = 'Read';

	public $AdminSettings = array('linkLabel' => 'List Page',
									'linkTab' => 'Content',
									'headerTitle' => 'List Pages',
									'linkContainer' => 'CMS');
	protected $pages;

	public function logic()
	{
		$packageList = new PackageInfo('BentoCMS');

		$modules = $packageList->getModules('Read');
		$db = dbConnect('default_read_only');

		$list = array();
		foreach($modules as $module)
		{
			$pageList = new BentoCMSPageList($module['modId']);
			$moduleLocation = (string) new Location($module['locationId']);
			$list[$moduleLocation] = $pageList->getPages(array('active', 'draft'));
		}

		$this->pages = $list;
	}

	public function viewAdmin()
	{
		foreach($this->pages as $locationString => $pageList)
		{
			$table = new HtmlTable('Table', array('name', 'editLink', 'deleteLink', 'pageLink'));
			$table->addClass(array('listing'));
			$x = 1;


			foreach($pageList as $pageName => $page)
			{

				$table['name'] = $pageName;

				$url = new Url();
				$url->property('module', $page->property('module'));
				$url->property('engine', 'Admin');

				$url->property('id', $page->property('id'));


				$url->property('action', 'EditPage');
				$editLink = new HtmlObject('a');
				$editLink->property('href', (string) $url);
				$editLink->wrapAround('Edit');

				$table['editLink'] = (string) $editLink;


				$url->property('action', 'RemovePage');
				$deleteLink = new HtmlObject('a');
				$deleteLink->property('href', (string) $url);
				$deleteLink->wrapAround('Delete');

				$table['deleteLink'] = (string) $deleteLink;




				$table['pageLink'] = 'blah blah blah';
				$table->nextRow();
			}
		}

		return $table->makeDisplay();
	}





}




?>