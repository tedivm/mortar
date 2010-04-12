<?php

class GraffitiPluginModelTagsToArray
{

	public function toArray(Model $model)
	{
		$array = array();

		if(GraffitiTagger::canTagModelType($model->getType())) {
			$loc = $model->getLocation();
			$allTags = GraffitiTagLookUp::getTagsFromLocation($loc);
			if($owner = $loc->getOwner()) {
				$ownerTags = GraffitiTagLookUp::getUserTags($loc, $owner);
			} else {
				$ownerTags = array();
			}

			$tags = array();
			foreach($allTags as $id => $weight) {
				$tags[] = array('tag' => GraffitiTagLookUp::getTagFromId($id), 'weight' => $weight);
			}

			$oTags = array();
			foreach($ownerTags as $id) {
				$oTags[] = GraffitiTagLookUp::getTagFromId($id);
			}

			$array['tags'] = $tags;
			$array['ownerTags'] = $oTags;
		}

		return $array;
	}
}

?>