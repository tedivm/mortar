<?php

class LithoActionPageDiff extends LithoActionPageRead
{
        public $adminSettings = array( 'headerTitle' => 'Revision Difference', 'useRider' => true );

	public function logic()
	{

	}

	public function viewAdmin($page)
	{
                $query = Query::getQuery();
                $errors = array();

		if(isset($query['rev1']) && is_numeric($query['rev1'])) {
			$rev1n = $query['rev1'];
		} else {
			$errors[] = $query['rev1'] . " is not an existing revision.<br />";
		}
		
		if(isset($query['rev2']) && is_numeric($query['rev2'])) {
			$rev2n = $query['rev2'];
		} else {
			$errors[] = $query['rev2'] . " is not an existing revision.<br />";
		}
                
                if(isset($rev1n) && isset($rev2n) && ($rev2n < $rev1n)) {
                	$rev1n = $query['rev2'];
                	$rev2n = $query['rev1'];
                }

                if(isset($rev1n)) {
                        try {
                                $this->model->loadRevision((int) $rev1n);
                                $rev1 = $this->model['content'];
                        } catch(Exception $e) {
                                $errors[] = "$rev1n is not an existing revision.<br />";
                        }
                }
                if(isset($rev2n)) {
                        try {
                                $this->model->loadRevision((int) $rev2n);
                                $rev2 = $this->model['content'];
                        } catch(Exception $e) {
                                $errors[] = "$rev2n is not an existing revision.<br />";
                        }
                }

		if(count($errors) > 0) {
			$errorText = '';
			foreach($errors as $error) $errorText .= $error;
			throw new ResourceNotFoundError($errorText);
		}

		$this->adminSettings['titleRider'] = " - r$rev1n : r$rev2n";
		$title = $this->adminSettings['headerTitle'];
		$title .= ($this->adminSettings['useRider']) ? 	$this->adminSettings['titleRider'] : '';
		$this->setTitle($title);

		$diff = new DiffMatchPatch();
		$diffs = $diff->diff_main($rev1, $rev2);
		$diff->diff_cleanupSemantic($diffs);
		$diffHtml = $diff->diff_prettyHtml($diffs);
		return $diffHtml;
	}

	public function viewHtml($page)
	{
		return $this->viewAdmin($page);
	}
}

?>