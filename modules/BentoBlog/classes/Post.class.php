<?php
AutoLoader::import('BentoCMS');

class BentoBlogPost extends BentoCMSCmsPage
{
	protected $type = 'Post';

	protected $author;
	protected $tags;

	public function __construct($id = false)
	{
		parent::__construct($id);

		$cache = new Cache('modules', 'cms', $id, 'blogInfo');
		$blogInfo = $cache->getData();

		if(!$cache->cacheReturned)
		{
			$db = dbConnect('default_read_only');
			$tagRows = $db->stmt_init();
			$tagRows->prepare('SELECT tag FROM BentoBlog_BlogHasTags WHERE location_id = ?');
			$tagRows->bind_param_and_execute('i', $this->id);

			while($tagResults = $tagRows->fetch_array())
			{
				$blogInfo['tags'][] = $tagResults['tag'];
			}


			$postRow = $db->stmt_init();
			$postRow->prepare('SELECT user_id FROM BentoBlog_postDetail WHERE location_id = ?');
			$postRow->bind_param_and_execute('i', $this->id);

			if($postRow->num_rows == 1)
			{
				$postDetails = $postRow->fetch_array();
				$blogInfo['author'] = $postDetails['user_id'];
			}
			$cache->storeData($blogInfo);
		}

		$this->tags = $blogInfo['tags'];
		$this->author = $blogInfo['author'];
	}

	public function save()
	{
		parent::save();

		$db = dbConnect('default');
		$removalQuery = $db->stmt_init();
		$removalQuery->prepare('DELETE FROM BentoBlog_BlogHasTags WHERE location_id = ?');
		$removalQuery->bind_param_and_execute('i', $this->id);

		if(is_array($this->tags))
			foreach($this->tags as $tag)
		{
			$insertQuery = $db->stmt_init();
			$insertQuery->prepare('INSERT INTO BentoBlog_BlogHasTags (location_id, tag) VALUES (?, ?)');
			$insertQuery->bind_param_and_execute('is', $this->id, $tag);
		}

		$postInfoQuery = $db->stmt_init();
		$postInfoQuery->prepare('REPLACE INTO BentoBlog_postDetail (location_id, user_id) VALUES (?, ?)');
		$postInfoQuery->bind_param_and_execute('ii', $this->id, $this->author);

		Cache::clear('modules', 'cms', $id, 'blogInfo');
		return true;
	}

}