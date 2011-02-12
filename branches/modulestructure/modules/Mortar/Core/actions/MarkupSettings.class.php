<?php

class MortarCoreActionMarkupSettings extends FormAction
{
	public static $settings = array( 'Base' => array('headerTitle' => 'Markup Settings') );

	static $requiredPermission = 'System';
	protected $formName = 'Form';
	protected $modelList;

	public function logic()
	{
		$this->modelList = ModelRegistry::getModelList();

		if(!$this->modelList || count($this->modelList) < 1)
			throw new CoreError('Unable to load modules from system.');

		return parent::logic();
	}

	protected function getForm()
	{
		$form = parent::getForm();
		$form->changeSection('engines')->
			setLegend('Model Markup');

		$engines = Markup::getEngines();
		$options = array('empty' => array('value' => ' ', 'label' => ' ', 'properties' => array()));

		foreach($engines as $engine) {
			$item = array('value' => $engine, 'label' => ucfirst($engine), 'properties' => array());
			$options[$engine] = $item;
		}

		foreach($this->modelList as $model) {
			if(!($handler = ModelRegistry::getHandler($model)))
				continue;

			if(in_array($handler['resource'], array('Root', 'TrashCan', 'TrashBag')))
				continue;

			if(!($instance = ModelRegistry::loadModel($handler['resource'])))
				continue;

			if(!(method_exists($instance, 'getLocation')))
				continue;

			if(!($def = staticHack(get_class($instance), 'richtext')))
				continue;

			$engine = Markup::loadModelEngine($model, true);

			$input = $form->createInput('model_' . $model . '_markup')->
				setLabel($model)->
				setType('select')->
				setValue($engine)->
				setPosttext('(Default: ' . ucfirst($def) . ')')->
				setOptions($options);
		}

		$form->changeSection('post')->
			setLegend('Post-Processing');

		$posts = Markup::getPost();

		foreach($posts as $post) {
			$value = Markup::getMarkupPost($post);
			$form->createInput('post_' . $post)->
				setType('checkbox')->
				check($value)->
				setLabel(ucwords($post));
		}

		return $form;
	}

	protected function processInput($input)
	{
		foreach($this->modelList as $model) {
			if(!isset($input['model_' . $model . '_markup']))
				continue;

			$engine =  $input['model_' . $model . '_markup'];
			if($engine !== ' ') {
				Markup::setModelEngine($model, $engine);
			} else {
				Markup::clearModelEngine($model);
			}
		}

		$posts = Markup::getPost();

		foreach($posts as $post) {
			if(isset($input['post_' . $post]) && $input['post_' . $post]) {
				Markup::setMarkupPost($post, true);
			} else {
				Markup::setMarkupPost($post, false);
			}
		}

		return true;
	}

	public function viewAdmin($page)
	{
		return $this->viewAdminForm();
	}

}

?>