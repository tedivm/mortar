<?php

class RecursiveListing extends LocationListing
{
	protected $maxLimit = 500;

	protected function getModels($number, $offset)
	{
		$originalParent = $this->restrictions['parent'];

		$modelList = parent::getModels($number, $offset);
		$sortedModels = array();

		if ($modelList) {
			foreach($modelList as $model) {
				$mmodel = ModelRegistry::loadModel($model['type'], $model['id']);
				$location = $mmodel->getLocation();
				$this->restrictions['parent'] = $location->getId();
				$descentModels = $this->getModels($this->maxLimit, 0);

				$sortedModels[] = $model;

				if($descentModels !== false) {
					$sortedModels = array_merge($sortedModels, $descentModels);
				}
			}
		}

		$this->restrictions['parent'] = $originalParent;

		return $sortedModels;
	}

        protected function getModelListingClass()
        {
                $listingClass = $this->listingClass;
                $listingObject = new $listingClass();

                $query = Query::getQuery();

                $listingObject->addRestriction('parent', $this->model->getLocation()->getId());

                return $listingObject;
        }

}

?>