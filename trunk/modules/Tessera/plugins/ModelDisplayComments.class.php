<?php

class TesseraPluginModelDisplayComments
{

	public function getExtraContent($model)
	{
		$type = $model->getType();

		if(TesseraComments::canCommentModelType($type)) {
			$loc = $model->getLocation();
			if($commentLoc = $loc->getChildByName('discussion')) {
				$page = ActivePage::getInstance();
				$theme = $page->getTheme();
				$discussion = $commentLoc->getResource();
				$view = new ViewModelTemplate($theme, $discussion, 'Display.html');
				$converter = $discussion->getModelAs('Html');
				$converter->useView($view);
				$converter->useTheme($theme);

				return array('comments' => $converter->getOutput());
			}
		}
	}
}

?>