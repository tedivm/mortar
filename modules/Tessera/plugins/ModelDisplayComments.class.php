<?php

class TesseraPluginModelDisplayComments
{

	public function getExtraContent($model)
	{
		$type = $model->getType();

		if(TesseraComments::canCommentModelType($type)) {
			$loc = $model->getLocation();
			if($commentLoc = $loc->getChildByName('discussion')) {
				$discussion = $commentLoc->getResource();
				$converter = $discussion->getModelAs('Html');
				return array('comments' => $converter->getOutput());
			}
		}
	}
}

?>