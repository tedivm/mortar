<?php

class ModelToHtml
{

	static public function convert($model, $handler)
	{
		$display = new DisplayMaker();

		if($display->loadTemplate($model->getType() . 'Display', $model->getModule()))
		{
			$tags = $display->tagsUsed();

			$location = $model->getLocation();

			if($index = array_search('createdOn', $tags))
			{
				$display->addDate('createdOn', $location->getCreationDate());
				unset($tags[$index]);
			}

			if($index = array_search('lastModified', $tags))
			{
				$display->addDate('lastModified', $location->getLastModified());
				unset($tags[$index]);
			}

			if($index = array_search('permalink', $tags))
			{
				$url = new Url();
				$url->location = $location;
				$display->addContent('permalink', (string) $url);
				unset($tags[$index]);
			}

			if(is_array($tags))
				foreach($tags as $tagName)
			{

				if(strpos($tagName, 'attr_') === 0)
				{
					$tagName = substr($tagName, 5);
					if(isset($model->$tagName))
						$display->addContent($tagName, $model->tagName);

				}elseif(isset($model[$tagName])){
					$display->addContent($tagName, $model[$tagName]);
				}

			}

			return $display->makeDisplay();

		}else{
			if(isset($model['content']))
				return $model['content'];
		}
	}

}

?>