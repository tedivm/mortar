<?php

class FilterHtmlEntities implements Filter
{
	public function filter($object)
	{
		return htmlentities($object);
	}
}

?>