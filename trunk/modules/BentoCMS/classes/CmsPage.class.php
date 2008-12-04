<?php

class BentoCMSCmsPage
{
	protected $module;
	protected $name;
	protected $currentVersion;
	protected $keywords;
	protected $description;
	protected $createdDate;
	protected $id;
	
	protected $content;
	
	public function __construct($id = false)
	{

		if($id)
		{
			$cache = new Cache('modules', 'cms', $id);
			$CmsInfo = $cache->getData();
			
			if(!$cache->cacheReturned)
			{
				$db = dbConnect('default_read_only');
				$stmt = $db->stmt_init();
				$stmt->prepare('SELECT * FROM cmsPages WHERE pageId = ?');
				$stmt->bind_param_and_execute('i', $id);
				
				$CmsInfo = ($stmt->num_rows > 0) ? $stmt->fetch_array() : false;
				$cache->storeData($CmsInfo);
			}

			if($CmsInfo !== false)
			{
				$this->id = $id;	
				$this->module = $CmsInfo['mod_id'];
				$this->name = $CmsInfo['pageName'];
				$this->currentVersion = $CmsInfo['pageCurrentVersion'];
				$this->keywords = $CmsInfo['pageKeywords'];
				$this->description = $CmsInfo['pageDescription'];
				$this->createDate = $CmsInfo['creationDate'];			
			}
		}
	}
	
	public function property($name, $value = '')
	{
		if(is_scalar($name) && $value != '')
		{
			$name = array($name => $value);
		}elseif(is_string($name)){
			if(property_exists(get_class($this), $name))
				return $this->$name;
		}
		
		if(is_array($name))
		{
			$properties = $name;
			
			foreach($properties as $name => $value)
			{
				if(property_exists(get_class($this), $name))
					$this->$name = $value;
			}
			
			return true;
		}
		
		return false;
	}
	
	public function newRevision()
	{
		if(!isset($this->id))
			return false;
			
		return new BentoCMSClassCmsContent($this->id);
	}
	
	public function getRevision($revision = 0)
	{
		$revision = ($revision != 0 && is_numeric($revision)) ? $revision : $this->currentVersion;
		return new BentoCMSClassCmsContent($this->id, $revision);
	}
	
	public function save()
	{
		$pageRecord = new ObjectRelationshipMapper('cmsPages');
		
		if(isset($this->id))
		{
			$pageRecord->pageId = $this->id;
			$pageRecord->select();
		}
		
		$pageRecord->mod_id = $this->module;
		$pageRecord->pageName = $this->name;
		$pageRecord->pageKeywords = $this->keywords;
		$pageRecord->pageDescription = $this->description;
		
		if(!isset($this->id))
		{
			$pageRecord->query_set('creationDate', 'NOW()');
		}
		
		$pageRecord->save();
		
		if(!isset($this->id))
		{
			
		}
		
		
		
		
		
		$this->id = $pageRecord->pageId;
		return is_numeric($this-id);
	}
}


class BentoCMSClassCmsContent
{
	protected $pageId;
	protected $version;
	
	
	protected $author;
	protected $timestamp;
	protected $title;
	protected $content;
	protected $rawContent;
	protected $id;
	protected $filters = array();
	
	public function __construct($pageId, $revision = false)
	{
		if(is_numeric($pageId) && is_numeric($revision))
		{
			$cache = new Cache('modules', 'cms', $id, 'content', $revision);
			$contentData = $cache->getData();
			
			if(!$cache->cacheReturned)
			{
				$db = dbConnect('default_read_only');
				$contentStmt = $db->stmt_init();
				$contentStmt->prepare('SELECT * FROM cmsContent WHERE pageId = ? AND contentVersion = ?');
				$contentStmt->bind_param_and_execute('ii', $pageId, $revision);

				
				$contentData = ($contentStmt->num_rows == 1) ? $contentStmt->fetch_array() : false;
				$cache->storeData($contentData);
			}

			if($contentData !== false)
			{
				$this->pageId = $contentData['pageId'];
				$this->version = $contentData['contentVersion'];
				$this->author = $contentData['contentAuthor'];
				$this->timestamp = $contentData['updateTime'];
				$this->title = $contentData['title'];
				$this->content = $contentData['content'];
				$this->rawContent = $contentData['rawContent'];
				$this->id = $revision;
			}
		}elseif(is_numeric($pageId)){
			$this->pageId = $pageId;
		}
	}
	
	public function property($name, $value = '')
	{
		if(is_scalar($name) && $value != '')
		{
			$name = array($name => $value);
		}elseif(is_string($name)){
			if(property_exists(get_class($this), $name))
				return $this->$name;
		}
		
		if(is_array($name))
		{
			$properties = $name;
			
			foreach($properties as $name => $value)
			{
				if(property_exists(get_class($this), $name))
					$this->$name = $value;
			}
			
			return true;
		}
		
		return false;
	}
	
	public function save()
	{
		if(!$this->author && class_exists('ActiveUser', false))
		{
			$user = ActiveUser::getInstance();
			$this->author = $user->getId();
		}
		
		
		$db = dbConnect('default');
		$insertStmt = $db->stmt_init();

		$insertStmt->prepare('INSERT INTO cmsContent (pageId,
													 contentVersion,
													contentAuthor, updateTime, 
													title, content, rawContent) 
												VALUES
													 (?,
													(IFNULL(  ((SELECT contentVersion FROM cmsContent AS tempContent WHERE tempContent.pageId = ? ORDER BY tempContent.contentVersion DESC LIMIT 1) + 1), 0)),
													?, NOW(),
													?, ?, ?)');
		
		$insertStmt->bind_param_and_execute('iiisss', $this->pageId, $this->pageId, $this->author, $this->title, $this->filterContent($this->content), $this->content);
		
		$getStmt = $db->stmt_init();
		$getStmt->prepare('SELECT contentVersion FROM cmsContent WHERE pageId = ? AND contentAuthor = ? AND title = ? ORDER BY contentVersion DESC LIMIT 1');
		$getStmt->bind_param_and_execute('iis', $this->pageId, $this->author, $this->title);
		$newRow = $getStmt->fetch_array();		
		$this->id = $newRow['contentVersion'];

		return is_numeric($this->id);
	}
	
	protected function filterContent($content)
	{
		foreach($this->filters as $filter)
		{
			$content = $filter->clean($content);
		}
		return $content;
	}
	
	public function addFilter($filter)
	{
		if($filter instanceof Filter)
		{
			$this->filters[] = $filter;
		}
	}
	
	public function makeActive()
	{
		
		if(!isset($this->id))
			$this->save();
			
		$db = dbConnect('default');
		$stmt = $db->stmt_init();
		$stmt->prepare('UPDATE cmsPages SET pageCurrentVersion = ? WHERE pageId = ?');
		$result = $stmt->bind_param_and_execute('ii', $this->id, $this->pageId);

	}
}

interface Filter
{
	public function clean($content);
}


?>