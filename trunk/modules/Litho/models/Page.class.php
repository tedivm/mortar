<?php

class LithoModelPage extends LocationModel
{
	static public $type = 'Page';
	static public $usePublishDate = true;
	protected $table = 'lithoPages';
	public $allowedChildrenTypes = array();
	protected $excludeFallbackActions = array('Index');

	protected $firstSave;

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
			if($revision->rawContent != $this->content['rawContent'] ||
					$revision->filteredContent != $this->content['filteredContent']	||
					$revision->title != $this->content['title'])
			{
				$revision = new PageRevision($this->getId(), $this->activeRevision);
				$this->saveRevision($revision);
			}else{
				$revision->makeActive();
			}

		}elseif (!isset($this->firstSave) || !$this->firstSave) {
				$revision = new PageRevision($this->getId());
				$this->saveRevision($revision);
		}
		return true;
	}

	protected function firstSaveLocation()
	{
		$revision = new PageRevision($this->getId());
		$this->saveRevision($revision);
		$this->firstSave = true;
	}

	protected function saveRevision($revision)
	{
		$revision->rawContent = $this->content['rawContent'];
		$revision->filteredContent = $this->content['filteredContent'];
		$revision->title = $this->content['title'];

		if(isset($this->content['note']))
			$revision->note = $this->content['note'];

		$user = ActiveUser::getUser();
		$revision->author = $user->getId();
		if($revision->save())
			$revision->makeActive();
	}

	public function loadRevision($id)
	{
//		$revision = new PageRevision($this->getId(), $id);
		$revision = $this->getRevision($id);
		$this->content['title'] = $revision->title;
		$this->content['filteredContent'] = $revision->filteredContent;
		$this->content['rawContent'] = $revision->rawContent;
		if(isset($this->content['author']) && $this->content['author'] != $revision->author)
			$this->content['lastEditor'] = $revision->author;
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
		$stmt->prepare('SELECT COUNT(revisionId) FROM lithoContent WHERE pageId = ?');
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
									FROM lithoContent
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
	public $note;

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
				$contentStmt->prepare('SELECT * FROM lithoContent WHERE pageId = ? AND revisionId = ?');
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
				$this->note = $contentData['note'];
			}else{
				throw new CoreError('Invalid page revision');
			}
		}else{

			if(!is_numeric($this->pageId))
				throw new CoreError('Page ID required required to load revision');


			if(!is_numeric($revisionId))
				throw new CoreError('Revision ID required required to load revision');
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

		$selectStmt = $db->stmt_init();
		$selectStmt->prepare('SELECT revisionId
									FROM lithoContent AS tempContent
									WHERE tempContent.pageId = ?
									ORDER BY tempContent.revisionId DESC LIMIT 1');

		$selectStmt->bindAndExecute('i', $this->pageId);


		if($selectStmt->num_rows === 0)
		{
			$revisionId = 1;
		}else{
			$results = $selectStmt->fetch_array();
			$revisionId = $results['revisionId'] + 1;
		}

		$insertStmt = $db->stmt_init();
		$insertStmt->prepare('INSERT INTO lithoContent
										(pageId,
										revisionId,
										author, updateTime, note,
										title, filteredContent, rawContent)
									VALUES
										 (?, ?,
											?, ?, ?,
											?, ?, ?)');

		$insertStmt->bindAndExecute('iiisssss', $this->pageId, $revisionId,
														$this->author, gmdate('Y-m-d H:i:s'), $this->note,
														$this->title, $this->filteredContent, $this->rawContent);

		$this->revisionId = $revisionId;

		return is_numeric($this->revisionId);
	}

	public function makeActive()
	{
		if(!is_numeric($this->getId()))
			return false;

		$db = DatabaseConnection::getConnection('default');
		$stmt = $db->stmt_init();
		$stmt->prepare('UPDATE lithoPages SET activeRevision = ? WHERE id = ?');
		return $stmt->bindAndExecute('ii', $this->revisionId, $this->pageId);
	}





}

?>