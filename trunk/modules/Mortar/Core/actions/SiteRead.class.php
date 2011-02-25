<?php

class MortarCoreActionSiteRead extends MortarCoreActionDirectoryRead
{
	public function logic()
	{
		$query = Query::getQuery();
		if(isset($query['id']))
			throw new ResourceNotFoundError('Invalid file name presented to site class.');
	}
}

?>