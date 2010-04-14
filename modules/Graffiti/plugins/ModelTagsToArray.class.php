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

			$query = Query::getQuery();
			$url = new Url();
			$url->format = $query['format'];
			$url->module = 'Graffiti';
			$url->action = 'TagInfo';

			$ourl = clone($url);
			$ourl->owner = 'true';

			$tags = array();
			foreach($allTags as $id => $weight) {
				$tag = GraffitiTagLookUp::getTagFromId($id);
				$turl = clone($url);
				$turl->tag = $tag;
				$tags[] = array('tag' => $tag,
						'weight' => $weight,
						'url' => (string) $turl);
			}

			$oTags = array();
			foreach($ownerTags as $id) {
				$tag = GraffitiTagLookUp::getTagFromId($id);
				$turl = clone($ourl);
				$turl->tag = $tag;
				$oTags[] = array('tag' => $tag, 'url' => (string) $turl);
			}

			$array['tags'] = $tags;
			$array['authorTags'] = $oTags;
		}

		return $array;
	}
}

?>