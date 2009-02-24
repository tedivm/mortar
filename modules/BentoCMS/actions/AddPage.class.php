<?php


class BentoCMSActionAddPage extends FormAction
{
	static $requiredPermission = 'Add';

	public $AdminSettings = array('linkLabel' => 'Create Page',
									'linkTab' => 'Content',
									'headerTitle' => 'Add Page',
									'linkContainer' => 'CMS');

	protected $formName = 'BentoCMSPageForm';
	protected $resourceClass = 'BentoCMSCmsPage';

	protected $resource;

	protected function processInput($inputHandler)
	{
		$user = ActiveUser::getInstance();

		$resource = $this->resourceClass;
		$cms = new $resource();
		$this->resource = $cms;
		$cms->property(array('parent' => $this->location, 'name' => $inputHandler['name'],
							'keywords' => $inputHandler['keywords'],
							'description' => $inputHandler['description']));
		$cms->save();

		$content = $cms->newRevision();
		$content->property(array('content' => $inputHandler->getRaw('content'), 'title' => $inputHandler['title'],
							'author', $user->getId()));

		$content->save();
		$content->makeActive();
		return true;
	}


	public function viewAdmin()
	{
		if($this->form->wasSubmitted())
		{
			if($this->formStatus)
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