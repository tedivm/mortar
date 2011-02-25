<?php

class TesseraCorePluginModelDisplayComments
{
	public function getExtraContent($model)
	{
		$type = $model->getType();

		$content = array();

		if(!TesseraCoreComments::canCommentModelType($type))
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

		if(!$model->checkAuth('Add'))
			return $content;

		if($name === 'Guest')
			$name = '';

		$query = Query::getQuery();
		$url = new Url();
		$url->location = $query['location'];
		$url->format = $query['format'];
		$url->action = 'PostComment';

		$form = new TesseraCorePostCommentForm('comment-form', $discussion, $name);
		$form->setAction($url);

		$content['commentform'] = $form->getFormAs('Html');

		return $content;
	}
}

?>