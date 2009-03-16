<?php

class ModelToHtml
{
	static public function convert($model)
	{
		$display = new DisplayMaker();
		$display->loadTemplate($modelName, $this->package);

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
	}

}

?>