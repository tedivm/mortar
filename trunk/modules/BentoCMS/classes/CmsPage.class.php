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
	protected $status;
	protected $parent;

	protected $location;
	protected $type = 'page';


	public function __construct($id = false)
	{

		if($id)
		{
			$cache = new Cache('modules', 'cms', $id);
			$CmsInfo = $cache->getData();

			if(!$cache->cacheReturned)
			{
				try{
					$location = new Location($id);

					if($location->getResource() != $this->type)
						throw new BentoWarning('Wrong resource type- expecting ' . $this->type .
												' but received ' . $location->getResource());

					$CmsInfo['id'] = $location->getId();
					$CmsInfo['createdOn'] = $location->getCreationDate();
					$CmsInfo['name'] = $location->getName();

					$db = dbConnect('default_read_only');
					$stmt = $db->stmt_init();
					$stmt->prepare('SELECT * FROM BentoCMS_Pages WHERE location_id = ?');
					$stmt->bind_param_and_execute('i', $id);

					if($stmt->num_rows != 1)
						throw new BentoWarning('CmsPage information did not load');

					$CmsInfo = array_merge($CmsInfo, $stmt->fetch_array());

				}catch(Exception $e){
					$CmsInfo = false;
				}

				$cache->storeData($CmsInfo);
			}

			if($CmsInfo !== false)
			{
				$this->id = $id;
				$this->name = $CmsInfo['name'];
				$this->currentVersion = $CmsInfo['pageCurrentVersion'];
				$this->keywords = $CmsInfo['pageKeywords'];
				$this->description = $CmsInfo['pageDescription'];
				$this->createdDate = $CmsInfo['createdOn'];
				$this->status = $CmsInfo['pageStatus'];
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
		$pageRecord = new ObjectRelationshipMapper('BentoCMS_Pages');

		if(isset($this->id))
		{
			$location = new Location($this->id);

			$pageRecord->location_id = $this->id;
			$pageRecord->select();
		}else{
			$location = new Location();
			$location->resource = $this->type;
		}

		$location->name = $this->name;
		$location->parent = ($this->parent instanceof Location) ? $this->parent->getId() : $this->parent;
		$location->save();

		if(!isset($this->id))
		{
			$this->id = $location->getId();
		}

		$pageRecord->location_id = $this->id;
		$pageRecord->pageKeywords = $this->keywords;
		$pageRecord->pageDescription = $this->description;
		$pageRecord->pageStatus = (isset($this->status)) ? $this->status : 'active';
		$pageRecord->save();

		Cache::clear('modules', 'cms', $this->id);

		return is_numeric($this-id);
	}

}


class BentoCMSClassCmsContent
{
	protected $locationId;
	protected $version;


	protected $author;
	protected $timestamp;
	protected $title;
	protected $content;
	protected $rawContent;
	protected $id;
	protected $filters = array();

	public function __construct($locationId, $revision = false)
	{
		if(is_numeric($locationId) && is_numeric($revision))
		{
			$cache = new Cache('modules', 'cms', $locationId, 'content', $revision);
			$contentData = $cache->getData();

			if(!$cache->cacheReturned)
			{
				$db = dbConnect('default_read_only');
				$contentStmt = $db->stmt_init();
				$contentStmt->prepare('SELECT * FROM BentoCMS_Content WHERE location_id = ? AND contentVersion = ?');
				$contentStmt->bind_param_and_execute('ii', $locationId, $revision);


				$contentData = ($contentStmt->num_rows == 1) ? $contentStmt->fetch_array() : false;
				$cache->storeData($contentData);
			}

			if($contentData !== false)
			{
				$this->locationId = $contentData['location_id'];
				$this->version = $contentData['contentVersion'];
				$this->author = $contentData['contentAuthor'];
				$this->timestamp = $contentData['updateTime'];
				$this->title = $contentData['title'];
				$this->content = $contentData['content'];
				$this->rawContent = $contentData['rawContent'];
				$this->id = $revision;
			}else{
				throw new BentoError('Invalid page revision');
			}
		}elseif(is_numeric($locationId)){
			$this->locationId = $locationId;
		}else{
			throw new BentoError('Location ID required');
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

		$insertStmt->prepare('INSERT INTO BentoCMS_Content (location_id,
													 contentVersion,
													contentAuthor, updateTime,
													title, content, rawContent)
												VALUES
													 (?,
													(IFNULL(  ((SELECT contentVersion FROM BentoCMS_Content AS tempContent
																WHERE tempContent.location_id = ?
																ORDER BY tempContent.contentVersion DESC LIMIT 1) + 1),
															0)),
													?, NOW(),
													?, ?, ?)');

		$insertStmt->bind_param_and_execute('iiisss', $this->locationId, $this->locationId, $this->author, $this->title,
														$this->filterContent($this->content), $this->content);

		$getStmt = $db->stmt_init();
		$getStmt->prepare('SELECT contentVersion FROM BentoCMS_Content
								WHERE location_id = ? AND contentAuthor = ? AND title = ?
								ORDER BY contentVersion DESC LIMIT 1');
		$getStmt->bind_param_and_execute('iis', $this->locationId, $this->author, $this->title);
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
		$stmt->prepare('UPDATE BentoCMS_Pages SET pageCurrentVersion = ? WHERE location_id = ?');
		$result = $stmt->bind_param_and_execute('ii', $this->id, $this->locationId);

	}


}

interface Filter
{
	public function clean($content);
}


?>