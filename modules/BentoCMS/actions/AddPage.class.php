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

//		$form = new Form($this->actionName);
	//	$this->form = $form;

		$this->form = new BentoCMSPageForm($this->actionName);



		if($this->form->checkSubmit())
		{
			$inputHandler = $this->form->getInputhandler();
			$user = ActiveUser::getInstance();

			$cms = new BentoCMSCmsPage();
			$cms->property(array('parent' => $this->location, 'name' => $inputHandler['name'],
								'keywords' => $inputHandler['keywords'],
								'description' => $inputHandler['description']));
			$cms->save();

			$content = $cms->newRevision();
			$content->property(array('content' => $inputHandler['content'], 'title' => $inputHandler['title'],
								'author', $user->getId()));
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