<?php

class MortarCoreActionDirectorySetDefaultPage extends ModelActionLocationBasedEdit
{
        public static $settings = array( 'Base' => array('headerTitle' => 'Set Default Page', 'useRider' => false) );

	public static $requiredPermission = 'Edit';

	protected function getForm()
	{
		$form = new Form('set_default_page');
		$form->changeSection('default_page');
		$form->setLegend('Default Page');

		$loc = $this->model->getLocation();
		$id = $loc->getId();

		$pageInput = $form->createInput('default')->
			setType('location')->
			setLabel('Default Page')->
			property('startid', $id);

		if($default = $loc->getMeta('defaultPage', true)) {
			if($page = Location::getLocation($default)) {
				if($parent = $page->getParent()) {
					if($parent->getId() == $loc->getId()) {
						$pageInput->setValue($page->getId());
					}
				}
			}
		}

		return $form;
	}

	protected function processInput($input)
	{
		$location = $this->model->getLocation();

		if(isset($input['default']) && $dloc = Location::getLocation($input['default'])) {
			$location->setMeta('defaultPage', $input['default']);
		} else {
			$location->unsetMeta('defaultPage');
		}

		return $location->save();
	}

	protected function onSuccess()
	{

	}

}

?>