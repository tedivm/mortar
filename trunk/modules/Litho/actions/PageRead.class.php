<?php

class LithoActionPageRead extends ModelActionLocationBasedRead
{
	public function logic()
	{
		$query = Query::getQuery();
		if(isset($query['revision']) && is_numeric($query['revision']))
		{
			try{
				$this->model->loadRevision((int) $query['revision']);
			}catch(Exception $e){
				throw new ResourceNotFoundError('Unable to load page revision ' . $query['revision']);
			}
		}
		parent::logic();
	}
}

?>