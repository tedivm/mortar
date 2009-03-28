<?php

class BentoCMSModelPage extends AbstractModel
{
	static public $type = 'Page';
	protected $table = 'BentoCMS_Pages';
	protected $allowedParents = array('Site', 'Directory');

	protected $activeRevision;
	protected $filters = array();

	protected function load($id)
	{
		if(parent::load($id))
		{
			$this->loadRevision($this->content['activeRevision']);
			$this->activeRevision = $this->content['activeRevision'];
			unset($this->content['activeRevision']);
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

			if($revision->rawContent != $this->content['rawContent'] ||
					$revision->filteredContent != $this->content['content']	||
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
		$revision->rawContent = $this->content['content'];
		$revision->filteredContent = $this->filterContent($this->content['content']);
		$revision->title = $this->content['title'];

		$user = ActiveUser::getInstance();
		$revision->author = $user->getId();
		$revision->save();
		$revision->makeActive();
	}

	public function loadRevision($id)
	{
		$revision = new PageRevision($this->getId(), $id);

		$this->content['title'] = $revision->title;
		$this->content['content'] = $revision->filteredContent;
		$this->content['rawContent'] = $revision->rawContent;

		if($this->content['author'] != $revision->author)
			$this->content['lastEditor'] = $revision->author;
	}

	public function getRevision($id = null)
	{
		return new PageRevision($this->getId(), $id);
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


	public function __construct($pageId, $id = null)
	{
		$this->pageId = $pageId;

		if($id)
			$this->loadById($id);
	}

	public function getId($id)
	{
		return $this->revisionId;
	}

	protected function loadById($revisionId)
	{
		if(is_numeric($this->pageId) && is_numeric($revisionId))
		{

			$cache = new Cache('models', 'page', $this->pageId, 'content', $this->revisionId);
			$contentData = $cache->getData();

			if(!$cache->cacheReturned)
			{
				$db = dbConnect('default_read_only');
				$contentStmt = $db->stmt_init();
				$contentStmt->prepare('SELECT * FROM BentoCMS_Content WHERE pageId = ? AND revisionId = ?');
				$contentStmt->bind_param_and_execute('ii', $this->pageId, $revisionId);


				$contentData = ($contentStmt->num_rows == 1) ? $contentStmt->fetch_array() : false;
				$cache->storeData($contentData);
			}

			if($contentData !== false)
			{
				$this->pageId = $contentData['pageId'];
				$this->revisionId = $contentData['revisionId'];
				$this->author = $contentData['contentAuthor'];
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
			$user = ActiveUser::getInstance();
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
										?, NOW(),
										?, ?, ?)');

		$insertStmt->bind_param_and_execute('iiisss', $this->pageId, $this->pageId, $this->author, $this->title,
														$this->filteredContent,
														$this->rawContent);

		$getStmt = $db->stmt_init();
		$getStmt->prepare('SELECT revisionId FROM BentoCMS_Content
								WHERE pageId = ? AND author = ? AND title = ?
								ORDER BY revisionId DESC LIMIT 1');
		$getStmt->bind_param_and_execute('iis', $this->pageId, $this->author, $this->title);
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