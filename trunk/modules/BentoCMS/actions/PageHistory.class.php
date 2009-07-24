<?php

class LithoActionPageHistory extends ModelActionLocationBasedRead
{
	protected $revisionCount;
	protected $revisionList;
	public function logic()
	{
		$this->revisionCount = $this->model->getRevisionCount();
		$this->revisionList = $this->model->getRevisionList(10);
	}

	protected function revisionsToTable($format = 'html')
	{
		$table = new Table($this->model->getType() . '');
		foreach($this->revisionList as $revision)
		{
			$revision->author;
			$revision->updateTime;
			$revision->title;
			$revision->rawContent;
			$revision->filteredContent;

			if(!isset($users[$revision->author]))
				$users[$revision->author] = ModelRegistry::loadModel('User', $revision->author);

			$url = new Url();
			$url->location = $this->model->getLocation()->getId();
			$url->format = $format;
			$url->revision = $revision->getId();

			$table->addField('updateTime', $url->getLink($revision->updateTime));
			$table->addField('author', $users[$revision->author]['name']);

			if(isset($revision->note) && strlen($revision->note) > 0)
				$table->addField('note', '(' . $revision->note . ')');
			$table->newRow();
		}

		return $table->makeHtml();
	}

	public function viewAdmin($page)
	{
		parent::viewAdmin($page);
		$table = $this->revisionsToTable('Admin');
		return $table;
	}

	public function viewHtml($page)
	{
		$table = $this->revisionsToTable('Html');
		return $table;
	}


}

?>