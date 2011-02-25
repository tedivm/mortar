<?php

class TesseraCoreActionThreadPostReply extends ModelActionLocationBasedEdit
{
	public static $settings = array( 'Base' => array('headerTitle' => 'Post Reply') );

	public static $requiredPermission = 'Add';

	protected $allowed = true;

	public function logic()
	{
		$query = Query::getQuery();
		$url = new Url();
		$url->location = $query['location'];
		$url->format = $query['format'];
		$url->action = 'Read';

		if(!$loc = $this->model->getLocation()) {
			$this->ioHandler->addHeader('Location', (string) $url);
			return $this->allowed = false;		
		}

		if(!$loc->getStatus() === 'Open') {
			$this->ioHandler->addHeader('Location', (string) $url);
			return $this->allowed = false;		
		}

		return parent::logic();
	}

	protected function getForm()
	{
		return new TesseraCorePostReplyForm('post-reply', $this->model);
	}

	protected function processInput($input)
	{
		$loc = $this->model->getLocation();
		$user = ActiveUser::getUser();

		$message = ModelRegistry::loadModel('Message');
		$message->setParent($loc);
		$message->title = 'Re: ' . $this->model->title;
		$message['content'] = $input['post_text'];
		$message['owner'] = $user->getId();
		$message['author'] = $user['name'];

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