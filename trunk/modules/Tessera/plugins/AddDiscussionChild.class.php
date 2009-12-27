<?php

class TesseraPluginAddDiscussionChild
{

	public function runFirstSave($model)
	{
		$discussion = new TesseraModelDiscussion();
		$location = $discussion->getLocation();

		$discussion->setParent($model->getLocation());
		$discussion['title'] = 'Re: ' . $model['title'];
		$discussion->name = 'discussion';

		$user = ActiveUser::getUser();
		$location->setOwner($user);
		$location->setPublishDate($model->getLocation()->getPublishDate());

		return $discussion->save();
	}
}

?>