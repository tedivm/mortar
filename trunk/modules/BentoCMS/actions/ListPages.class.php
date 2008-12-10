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
			$list[$moduleLocation]['pageList'] = $pageList->getPages(array('active', 'draft'));
			$list[$moduleLocation]['moduleInfo'] = new ModuleInfo($module['modId']);
		}

		$this->pages = $list;
	}

	public function viewAdmin()
	{
		foreach($this->pages as $locationString => $moduleList)
		{
			$moduleInfo = $moduleList['moduleInfo'];
			$pageList = $moduleList['pageList'];

			//if(count($pageList) < 1)
			//	continue;

			$permissions = array();
			$columnArray = array('name');

			if($moduleInfo->checkAuth('Edit'))
			{
				$permissions['edit'] = true;
				$columnArray[] = 'editLink';
			}


			if($moduleInfo->checkAuth('Delete'))
			{
				$permissions['delete'] = true;
				$columnArray[] = 'deleteLink';
			}

			$columnArray[] = 'pageLink';

			$table = new HtmlTable($this->actionName . '_' . $moduleInfo['name'], $columnArray);
			$table->addClass(array('listing'));


			$table->setHeader($moduleInfo['Name'], count($columnArray) - 1);

			if($moduleInfo->checkAuth('Add'))
			{
				$url = new Url();
				$url->property('engine', 'Admin');
				$url->property('module', $moduleInfo['ID']);
				$url->property('action', 'AddPage');

				$table->setHeader($url->getLink('Add Page'));

			}


			$x = 1;

			foreach($pageList as $pageName => $page)
			{
				$table['name'] = $pageName;

				$url = new Url();
				$url->property('module', $page->property('module'));
				$url->property('engine', 'Admin');
				$url->property('id', $page->property('id'));

				if($permissions['edit'])
				{
					$url->property('action', 'EditPage');
					$editLink = new HtmlObject('a');
					$editLink->property('href', (string) $url);
					$editLink->wrapAround('Edit');
					$table['editLink'] = (string) $editLink;
				}

				if($permissions['delete'])
				{
					$url->property('action', 'RemovePage');
					$deleteLink = new HtmlObject('a');
					$deleteLink->property('href', (string) $url);
					$deleteLink->wrapAround('Delete');
					$table['deleteLink'] = (string) $deleteLink;
				}

				$url = new Url();
				$url->property('module', $page->property('module'));
				$url->property('id', $pageName);


				$table['pageLink'] = '<a href=' . $url . ' target="_blank">View</a>';
				$table->nextRow();
			}

			$output .= $table->makeDisplay();
		}

		return $output;
	}





}




?>