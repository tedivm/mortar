<?php

class TesseraPluginAllowDiscussionType
{
	public function getAllowedChildren($type)
	{
		if(TesseraComments::canCommentModelType($type))
			return array('Discussion');
		else
			return array();
	}
}

?>