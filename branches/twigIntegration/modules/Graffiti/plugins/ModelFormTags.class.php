<?php

class GraffitiPluginModelFormTags
{
	public function adjustForm(Model $model, Form $baseForm)
	{
		if(!GraffitiTagger::canTagModelType($model->getType()))
			return null;
	}
}

?>