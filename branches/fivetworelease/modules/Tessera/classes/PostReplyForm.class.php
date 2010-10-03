<?php

class TesseraPostReplyForm extends Form
{
	protected $thread;

	public function __construct($name, $thread)
	{
		$this->thread = $thread;
		parent::__construct($name);
	}

	protected function define()
	{
		$this->changeSection('post')->
			setLegend('Quick Post')->
			setMarkup(Markup::loadModelEngine($this->thread->getType()));

		$this->createInput('post_text')->
			setLabel('Post Text')->
			addRule('required')->
			setType('richtext');
	}
}

?>