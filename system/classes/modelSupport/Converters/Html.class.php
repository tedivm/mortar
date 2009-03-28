<?php

class ModelToHtml
{

	static public function convert($model, $handler)
	{
		$display = new DisplayMaker();

		if($display->loadTemplate($model->getType(), $model->getModule()))
		{
			$tags = $display->tagsUsed();

			foreach($tags as $tagName => $tagInfo)
			{
				if(strpos($tagName, 'attr_') === 0)
				{
					$tagName = substr($tagName, 5);

					if(isset($model[$tagName]))
						$display->addContent($tagName, $model[$tagName]);

				}elseif(isset($model->$tagName)){

					$display->addContent($tagName, $model->$tagName);
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