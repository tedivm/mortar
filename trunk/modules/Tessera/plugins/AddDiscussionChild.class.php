<?php

class TesseraPluginAddDiscussionChild
{

	public function runFirstSave($model)
	{
		$discussion = new TesseraModelDiscussion();
		$location = $discussion->getLocation();

		$discussion->setParent($model->getLocation());
		$discussion['title'] = 'Re: ' . $model['title'];

		$user = ActiveUser::getUser();
		$location->setOwner($user);
		$location->setPublishDate($model->getLocation()->getPublishDate());
		$location->setName('discussion');

		return $discussion->save();
	}
}

?>