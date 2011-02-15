<?php

class TesseraCorePluginAllowDiscussionType
{
	public function getAllowedChildren($type)
	{
		if(TesseraCoreComments::canCommentModelType($type))
			return array('Discussion');
		else
			return array();
	}
}

?>