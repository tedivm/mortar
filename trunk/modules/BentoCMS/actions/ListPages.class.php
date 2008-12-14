<?php

class BentoCMSActionListPages extends PackageAction
{
	static $requiredPermission = 'Read';

	public $AdminSettings = array('linkLabel' => 'List Page',
									'linkTab' => 'Content',
									'headerTitle' => 'List Pages',
									'linkContainer' => 'CMS');
	protected $pages;
	protected $pageTypes = array('active');

	protected $resourceType = 'Page';
	protected $resourceHandler = 'BentoCMSCmsPage';

	public function logic()
	{
		$packageList = new PackageInfo($this->package);
		$modules = $packageList->getModules('Read');

		$db = dbConnect('default_read_only');
		$list = array();

		foreach($modules as $module)
		{
			$moduleLocation = new Location($module['locationId']);
			$childrenLocations = $moduleLocation->getChildren($this->resourceType);

			$pages = array();
			$resourceHandler = $this->resourceHandler;

			if(is_array($childrenLocations))
				foreach($childrenLocations as $pageLocation)
			{
				$id = $pageLocation->getId();
				$pages[$pageLocation->getName()] = new $resourceHandler($id);
			}

			$list[(string) $moduleLocation]['pageList'] = $pages;
			$list[(string) $moduleLocation]['module'] = array('moduleLocation' => $moduleLocation, 'moduleId' => $module['modId']);
		}

		$this->pages = $list;
	}

	public function viewAdmin()
	{
		foreach($this->pages as $locationString => $moduleList)
		{
			$moduleLocation = $moduleList['module']['moduleLocation'];
			$moduleId = $moduleList['module']['moduleId'];
			$pageList = $moduleList['pageList'];

			$user = ActiveUser::getInstance();
			$modulePermission = new Permissions($moduleLocation, $user->getId());

			$columnArray = array('name');

			if($modulePermission->checkAuth('Edit'))
			{
				$permissions['edit'] = true;
				$columnArray[] = 'editLink';
			}


			if($modulePermission->checkAuth('Delete'))
			{
				$permissions['delete'] = true;
				$columnArray[] = 'deleteLink';
			}

			$columnArray[] = 'pageLink';

			$table = new HtmlTable($this->actionName . '_' . $moduleLocation->getName(), $columnArray);
			$table->addClass(array('listing'));


			$table->setHeader($moduleLocation->getName(), count($columnArray) - 1);

			if($modulePermission->checkAuth('Add'))
			{
				$url = new Url();
				$url->property('engine', 'Admin');
				$url->property('module', $moduleId);
				$url->property('action', 'Add' . $this->resourceType);

				$table->setHeader($url->getLink('Add Page'));

			}


			$x = 1;

			foreach($pageList as $pageName => $page)
			{
				if(!in_array($page->property('status'), $this->pageTypes))
					continue;

				$table['name'] = $pageName;

				$pageLocation = new Location($page->property('id'));
				$pagePermission = new Permissions($pageLocation, $user->getId());

				$url = new Url();
				$url->property('module', $moduleId);
				$url->property('engine', 'Admin');
				$url->property('id', $page->property('id'));

				if($pagePermission->checkAuth('Edit'))
				{
					$url->property('action', 'Edit' . $this->resourceType);
					$editLink = new HtmlObject('a');
					$editLink->property('href', (string) $url);
					$editLink->wrapAround('Edit');
					$table['editLink'] = (string) $editLink;
				}

				if($pagePermission->checkAuth('Delete'))
				{
					$url->property('action', 'Remove' . $this->resourceType);
					$deleteLink = new HtmlObject('a');
					$deleteLink->property('href', (string) $url);
					$deleteLink->wrapAround('Delete');
					$table['deleteLink'] = (string) $deleteLink;
				}

				$url = new Url();
				$url->property('module', $moduleId);
				$url->property('id', str_replace(' ', '_', $pageName));
				$url->property('action', 'View' . $this->resourceType);
				$table['pageLink'] = '<a href="' . $url . '" target="_blank">View</a>';
				$table->nextRow();
			}

			$output .= $table->makeDisplay();
		}

		return $output;
	}





}




?>