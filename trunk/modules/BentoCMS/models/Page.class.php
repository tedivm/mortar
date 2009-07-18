<?php

class BentoCMSModelPage extends LocationModel
{
	static public $type = 'Page';
	protected $table = 'BentoCMS_Pages';
	public $allowedChildrenTypes = array();

	protected $activeRevision;
	protected $filters = array();

	protected function load($id)
	{
		if(parent::load($id))
		{
			$this->loadRevision($this->content['activeRevision']);
			$this->activeRevision = $this->content['activeRevision'];
			unset($this->content['activeRevision']);
			return true;
		}else{
			return false;
		}
	}


	public function save($parent = null)
	{
		if(!isset($this->content['status']))
			$this->content['status'] = 'Published';

		if(!parent::save($parent))
			return false;

		if(isset($this->activeRevision))
		{
			$revision = $this->getRevision($this->activeRevision);
//			$revision = new PageRevision($this->getId(), $this->activeRevision);
			if($revision->rawContent != $this->content['rawContent'] ||
					$revision->filteredContent != $this->content['filteredContent']	||
					$revision->title != $this->content['title'])
			{
				$revision = new PageRevision($this->getId(), $this->activeRevision);
				$this->saveRevision($revision);
			}

		}else{
				$revision = new PageRevision($this->getId());
				$this->saveRevision($revision);
		}

		return true;
	}

	protected function saveRevision($revision)
	{
		$revision->rawContent = $this->content['rawContent'];
		$revision->filteredContent = $this->content['filteredContent'];
		$revision->title = $this->content['title'];

		$user = ActiveUser::getUser();
		$revision->author = $user->getId();
		$revision->save();
		$revision->makeActive();
	}

	public function loadRevision($id)
	{
//		$revision = new PageRevision($this->getId(), $id);
		$revision = $this->getRevision($id);
		$this->content['title'] = $revision->title;
		$this->content['filteredContent'] = $revision->filteredContent;
		$this->content['rawContent'] = $revision->rawContent;
		//if($this->content['author'] != $revision->author)
			//$this->content['lastEditor'] = $revision->author;
	}

	public function getRevision($id = null)
	{
		if(!$id)
			$id = $this->activeRevision;

		$revision = new PageRevision($this->getId());
		$revision->loadById($id);
		return $revision;
	}

	public function getRevisionCount()
	{
		if(!($pageId = $this->getId()))
			return 0;

		$stmt = DatabaseConnection::getStatement('default_read_only');
		$stmt->prepare('SELECT COUNT(revisionId) FROM BentoCMS_Content WHERE pageId = ?');
		$stmt->bindAndExecute('i', $pageId);
		$results = $stmt->fetch_array();

		if(is_numeric($results['COUNT(revisionId)']))
			return $results['COUNT(revisionId)'];

		return 0;
	}

	public function getRevisionList($quantity, $offset = 0)
	{
		if(!is_numeric($quantity) || !is_numeric($offset))
			throw new TypeMismatch(array('Integer'));

		$cache = new Cache('models', 'Page', $this->getId(), 'content', 'browseRevisions', $quantity, $offset);
		$revisionList = $cache->getData();
		if($cache->isStale())
		{
			$stmt = DatabaseConnection::getStatement('default_read_only');
			$stmt->prepare('SELECT revisionId
									FROM BentoCMS_Content
									WHERE pageId = ?
									ORDER BY updateTime DESC
									LIMIT ?,?');
			$stmt->bindAndExecute('iii', $this->getId(), $offset, $quantity);

			$revisionList = array();
			while($results = $stmt->fetch_array())
				$revisionList[] = $results['revisionId'];

			$cache->storeData($revisionList);
		}

		$returnRevisions = array();
		foreach($revisionList as $revisionId)
			$returnRevisions[] = $this->getRevision($revisionId);

		return (count($returnRevisions) > 0) ? $returnRevisions : false;
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

	public function __toArray()
	{
		$array = parent::__toArray();
		$array['content'] = $array['filteredContent'];
		unset($array['filteredContent']);
		return $array;
	}

	public function offsetGet($name)
	{
		if($name == 'content')
		{
			return $this->content['filteredContent'];
		}else{
			return parent::offsetGet($name);
		}
	}

	public function offsetSet($name, $value)
	{
		if($name == 'content')
		{
			$this->content['rawContent'] = $value;
			return $this->content['filteredContent'] = $this->filterContent($value);
		}else{
			return parent::offsetSet($name, $value);
		}
	}

	public function offsetExists($name)
	{
		return ($name == 'content') ? isset($this->content['filteredContent']) : parent::offsetExists($name);
	}
}


class PageRevision
{
	protected $pageId;
	protected $revisionId;

	public $author;
	public $updateTime;
	public $title;
	public $rawContent;
	public $filteredContent;


	public function __construct($pageId)
	{
		$this->pageId = $pageId;
	}

	public function getId()
	{
		return $this->revisionId;
	}

	public function loadById($revisionId)
	{
		if(is_numeric($this->pageId) && is_numeric($revisionId))
		{

			$cache = new Cache('models', 'Page', $this->pageId, 'content', $revisionId);
			$contentData = $cache->getData();

			if($cache->isStale())
			{
				$db = dbConnect('default_read_only');
				$contentStmt = $db->stmt_init();
				$contentStmt->prepare('SELECT * FROM BentoCMS_Content WHERE pageId = ? AND revisionId = ?');
				$contentStmt->bindAndExecute('ii', $this->pageId, $revisionId);


				$contentData = ($contentStmt->num_rows == 1) ? $contentStmt->fetch_array() : false;
				$cache->storeData($contentData);
			}

			if($contentData !== false)
			{
				$this->pageId = $contentData['pageId'];
				$this->revisionId = $contentData['revisionId'];
				$this->author = $contentData['author'];
				$this->updateTime = $contentData['updateTime'];
				$this->title = $contentData['title'];
				$this->rawContent = $contentData['rawContent'];
				$this->filteredContent = $contentData['filteredContent'];

			}else{
				throw new BentoError('Invalid page revision');
			}
		}elseif(is_numeric($locationId)){
			$this->locationId = $locationId;
		}else{
			throw new BentoError('Location ID required');
		}
	}

	public function save()
	{
		if(!$this->author && class_exists('ActiveUser', false))
		{
			$user = ActiveUser::getUser();
			$this->author = $user->getId();

		}

		$db = dbConnect('default');
		$insertStmt = $db->stmt_init();
		$insertStmt->prepare('INSERT INTO BentoCMS_Content
										(pageId,
										revisionId,
										author, updateTime,
										title, filteredContent, rawContent)
									VALUES
										 (?,
										(IFNULL(
											((SELECT revisionId
													FROM BentoCMS_Content AS tempContent
													WHERE tempContent.pageId = ?
													ORDER BY tempContent.revisionId DESC LIMIT 1) + 1),
											1)
										),
										?, ?,
										?, ?, ?)');

		$insertStmt->bindAndExecute('iiissss', $this->pageId, $this->pageId,
														$this->author, gmdate('Y-m-d H:i:s'),
														$this->title, $this->filteredContent, $this->rawContent);

		$getStmt = $db->stmt_init();
		$getStmt->prepare('SELECT revisionId FROM BentoCMS_Content
								WHERE pageId = ? AND author = ? AND title = ?
								ORDER BY revisionId DESC LIMIT 1');
		$getStmt->bindAndExecute('iis', $this->pageId, $this->author, $this->title);
		$newRow = $getStmt->fetch_array();
		$this->revisionId = $newRow['revisionId'];

		return is_numeric($this->revisionId);
	}

	public function makeActive()
	{
		$db = DatabaseConnection::getConnection('default');
		$stmt = $db->stmt_init();
		$stmt->prepare('UPDATE BentoCMS_Pages SET activeRevision = ? WHERE id = ?');
		return $stmt->bindAndExecute('ii', $this->revisionId, $this->pageId);
	}





}

?>