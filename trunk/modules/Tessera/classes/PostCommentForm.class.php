<?php

class TesseraPostCommentForm extends Form
{
	protected $discussion;
	protected $author;

	public function __construct($name, $discussion, $author)
	{
		$this->discussion = $discussion;
		$this->author = $author;
		parent::__construct($name);
	}

	protected function define()
	{
		$this->changeSection('comment')->
			setLegend('Comment')->
			setMarkup(Markup::loadModelEngine($this->discussion->getType()));

		$this->createInput('comment_author')->
			setLabel('Name')->
			addRule('required')->
			addRule('maxlength', 40)->
			setValue($this->author);

		$this->createInput('comment_text')->
			setLabel('Text')->
			addRule('required')->
			setType('richtext');
	}
}

?>