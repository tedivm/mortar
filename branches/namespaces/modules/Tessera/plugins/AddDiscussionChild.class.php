<?php

class TesseraPluginAddDiscussionChild
{
	public function runFirstSave($model)
	{
		if(TesseraComments::canCommentModelType($model->getType())) {
			$discussion = new TesseraModelDiscussion();
			$location = $discussion->getLocation();

			$discussion->setParent($model->getLocation());
			$discussion->title = 'Re: ' . $model->title;
			$discussion->name = 'discussion';

			$user = ActiveUser::getUser();
			$location->setOwner($user);

			return $discussion->save();
		} else {
			return true;
		}
	}
}

?>