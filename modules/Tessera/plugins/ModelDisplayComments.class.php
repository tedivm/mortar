<?php

class TesseraPluginModelDisplayComments
{
	public function getExtraContent($model)
	{
		$type = $model->getType();

		$content = array();

		if(!TesseraComments::canCommentModelType($type))
			return $content;

		$loc = $model->getLocation();

		if(!($commentLoc = $loc->getChildByName('discussion')))
			return $content;

		$discussion = $commentLoc->getResource();
		$converter = $discussion->getModelAs('Html');
		$content['comments'] = $converter->getOutput();

		if($commentLoc->getStatus() === 'Closed')
			return $content;

		$user = ActiveUser::getUser();
		$name = $user['name'];

		if($name === 'Guest')
			$name = '';

		$query = Query::getQuery();
		$url = new Url();
		$url->location = $query['location'];
		$url->format = $query['format'];
		$url->action = 'PostComment';

		$form = new TesseraPostCommentForm('comment-form', $discussion, $name);
		$form->setAction($url);

		$content['commentform'] = $form->getFormAs('Html');

		return $content;
	}
}

?>