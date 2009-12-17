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
		$users = array();

		$table = new Table($this->model->getType() . '');
		$table->addClass('revision-listing');
		$table->addClass('index-listing');

		$table->addColumnLabel('revision_id', 'Number');
		$table->addColumnLabel('revision_title', 'Title');
		$table->addColumnLabel('revision_author', 'Author');
		$table->addColumnLabel('revision_time', 'Time');
		$table->addColumnLabel('revision_note', 'Note');
		$table->addColumnLabel('revision_actions', 'Actions');

		foreach($this->revisionList as $revision)
		{
			$url = new Url();
			$url->location = $this->model->getLocation()->getId();
			$url->format = $format;
			$url->revision = $revision->getId();

			$radioButton  = "<input type='radio' name='rev1' value='" . $revision->getId() . "' />";
			$radioButton .= "<input type='radio' name='rev2' value='" . $revision->getId() . "' />";

			if(!isset($users[$revision->author]))
				$users[$revision->author] = ModelRegistry::loadModel('User', $revision->author);

			$table->addField('revision_id', $revision->getId());
			$table->addField('revision_title', $url->getLink($revision->title));
			$table->addField('revision_author', $users[$revision->author]['name']);
			$table->addField('revision_time', $revision->updateTime);
			if(isset($revision->note) && strlen($revision->note) > 0)
				$table->addField('revision_note', $revision->note);
			$table->addField('revision_actions', $radioButton);
			$table->newRow();
		}
		$url = new Url();
		$url->location = $this->model->getLocation()->getId();
		$url->format = $format;
		
		$diffAction = '<input type="submit" value="Diff" />';
		
		$table->addField('revision_actions', $diffAction);

		$formOpener = '<form name="Diff" action="'.(string) $url.'" method="get"><input type="hidden" name="action" value="Diff" />';

		return $formOpener . $table->makeHtml() . "</form>";
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