<?php

class MortarPluginModelFormCategories
{
	public function adjustForm(Model $model, Form $form)
	{
		if(!method_exists($model, 'getLocation'))
			return null;

		$list = MortarCategorizer::getDisplayTree();

		$form->changeSection('categories')->
			setLegend('Categories');

		$loc = $model->getLocation();
		if($locId = $loc->getId()) {
			$cats = MortarCategorizer::getLocationCategories($locId);
		} else {
			$cats = array();
		}

		foreach($list as $num => $cat) {
			$px = ($cat['level'] * 24);

			if((int) $cat['level'] === 0) {
				$pretext = '<fieldset class="category_form_top_level">';
			} else {
				$pretext = '<div style="float: left; width: '.$px.'px;">&nbsp</div>'; 
			}

			if(!isset($list[$num + 1]) || (int) $list[$num + 1]['level'] === 0) {
				$posttext = '</fieldset>';
			} else {
				$posttext = '';
			}

			$input = $form->createInput('category_' . $cat['id'])->
				setLabel($cat['name'])->
				setType('checkbox')->
				setPretext($pretext)->
				setPosttext($posttext)->
				property('style', 'position: relative; left: -'.$px.'px;');

			if(in_array($cat['id'], $cats)) {
				$input->check(true);
			}
		}
	}

	public function processAdjustedInputPost(Model $model, $input)
	{
		if(!method_exists($model, 'getLocation'))
			return null;

		$loc = $model->getLocation();

		$cats = MortarCategorizer::getDisplayTree();

		foreach($cats as $cat) {
			if(isset($input['category_' . $cat['id']])) {
				MortarCategorizer::categorizeLocation(	$loc->getId(), 
									$cat['id'], 
									$input['category_' . $cat['id']]);
			}
		}
	}
}

?>