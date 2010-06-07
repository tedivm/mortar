<?php

class TesseraPluginModelCommentsToArray
{

	public function toArray(Model $model)
	{
		$array = array();

		if(!TesseraComments::canCommentModelType($model->getType()))
			return $array;

		if(!method_exists($model, 'getLocation'))
			return $array;

		$loc = $model->getLocation();

		if($discussion = $loc->getChildByName('discussion')) {
			$count = 0;
			$comments = $discussion->getChildren('Message');
			foreach($comments as $comment) {
				$model = $comment->getResource();
				if($model->checkAuth('Read')) {
					$count++;
				}
			}
		}

		$array['comments'] = $count;

		return $array;
	}
}

?>