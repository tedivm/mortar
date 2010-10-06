<?php

class TesseraActionModelPostComment extends ModelActionLocationBasedEdit
{
	public static $settings = array( 'Base' => array('headerTitle' => 'Post Comment') );

	public static $requiredPermission = 'Add';

	protected $allowed = true;

	protected $discussion;

	public function logic()
	{
		$query = Query::getQuery();
		$url = new Url();
		$url->location = $query['location'];
		$url->format = $query['format'];
		$url->action = 'Read';

		if(!TesseraComments::canCommentModelType($this->model->getType())) {
			$this->ioHandler->addHeader('Location', (string) $url);
			return $this->allowed = false;
		}

		if(!method_exists($this->model, 'getLocation')) {
			$this->ioHandler->addHeader('Location', (string) $url);
			return $this->allowed = false;
		}

		$loc = $this->model->getLocation();
		if(!($this->discussion = $loc->getChildByName('discussion'))) {
			$this->ioHandler->addHeader('Location', (string) $url);
			return $this->allowed = false;		
		}

		return parent::logic();
	}

	protected function getForm()
	{
		$discussion = $this->discussion->getResource();
		$user = ActiveUser::getUser();
		$name = $user['name'];

		if($name === 'Guest')
			$name = '';

		return new TesseraPostCommentForm('comment-form', $discussion, $name);
	}

	protected function processInput($input)
	{
		$loc = $this->model->getLocation();
		$user = ActiveUser::getUser();

		if(!$discussion = $loc->getChildByName('discussion'))
			return false;

		$model = $discussion->getResource();

		$name = $user['name'];
		if($name === 'Guest')
			$name = $input['comment_author'];

		$message = new TesseraModelMessage();
		$message->setParent($discussion);
		$message->title = $model->title; var_dump($model->title);
		$message['content'] = $input['comment_text'];
		$message['author'] = $name;
		if(isset($input['comment_email']))
			$message['email'] = $input['comment_email'];
		if($user['name'] === 'Guest')
			$message['anonymous'] = 1;

		$messageLoc = $message->getLocation();
		$messageLoc->setOwner($user);

		return $message->save();
	}

	public function viewAdmin($page)
	{
		if(!$this->allowed)
			return false;

		return parent::viewAdmin($page);
	}
}

?>