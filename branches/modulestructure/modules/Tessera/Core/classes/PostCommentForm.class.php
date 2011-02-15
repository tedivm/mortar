<?php

class TesseraCorePostCommentForm extends Form
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

		$author = $this->createInput('comment_author')->
			setLabel('User Name')->
			addRule('required')->
			addRule('maxlength', 40)->
			setValue($this->author);

		if($this->author !== '') {
			$author->property('readonly', true);
		} else {
			$email = $this->createInput('comment_email')->
				setLabel('Email Address')->
				addRule('email')->
				addRule('required');
		}

		$this->createInput('comment_text')->
			setLabel('Comment Text')->
			addRule('required')->
			setType('richtext');
	}
}

?>