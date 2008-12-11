<?php


class BentoCMSActionAddPage extends Action
{
	static $requiredPermission = 'Add';

	public $AdminSettings = array('linkLabel' => 'Create Page',
									'linkTab' => 'Content',
									'headerTitle' => 'Add Page',
									'linkContainer' => 'CMS');

	protected $form;
	protected $success = false;
	public function logic()
	{
		$info = InfoRegistry::getInstance();

		$form = new Form($this->actionName);
		$this->form = $form;

		$form->changeSection('info')->
			setlegend('Page Information')->
			createInput('title')->
				setLabel('Title')->
				addRule('required')->
			getForm()->

			createInput('description')->
				setType('textarea')->
				setLabel('Description')->
				addRule('required')->
			getForm()->

			createInput('keywords')->
				setType('textarea')->
				setLabel('Keywords')->
				addRule('required')->
			getForm()->

			createInput('name')->
				setLabel('Name (as appears in URLS)')->
				addRule('required')->
			getForm()->
/*
			createInput('module')->
				setType('module')->
				setLabel('Module')->
				property('moduleName', 'BentoCMS')->
				addRule('required')->
			getForm()->
*/

			changeSection('content')->
			setlegend('Page Content')->
			createInput('content')->
				setType('html')->
				addRule('required');


		if($form->checkSubmit())
		{
			$inputHandler = $form->getInputhandler();
			$user = ActiveUser::getInstance();

			$cms = new BentoCMSCmsPage();
			$cms->property(array('parent' => $this->location, 'name' => $inputHandler['name'], 'keywords' => $inputHandler['keywords'], 'description' => $inputHandler['description']));
			$cms->save();

			$content = $cms->newRevision();
			$content->property(array('content' => $inputHandler['content'], 'title' => $inputHandler['title'], 'author', $user->getId()));
			$content->save();
			$content->makeActive();

			$this->success = true;


		}

	}

	public function viewAdmin()
	{
		if($this->form->wasSubmitted())
		{
			if($this->success)
			{
				$this->AdminSettings['headerSubTitle'] = 'Page successfully added';
				return '';
			}else{
				$this->AdminSettings['headerSubTitle'] = 'An error has occured while trying to process this form';
			}
		}else{

		}

		$output .= $this->form->makeDisplay();
		return $output;
	}
}

?>