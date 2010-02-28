<?php

class ViewControlDisplay
{

	protected $controlset;
	protected $theme;
	protected $template = 'support/Control.html';
	
	public function __construct(ControlSet $controlset, Theme $theme)
	{
		$this->controlset = $controlset;
		$this->theme = $theme;
	}

	public function useTemplate($template)
	{
		$this->template = $template;
	}

	public function getDisplay()
	{
		$controls = $this->controlset->getControls();

		$controlContent = '';

		foreach($controls as $key => $control) {
			$classes = $control->getClasses();
			$content = $control->getContent();
			$links = $this->getLinks($key);

			$controlView = new ViewThemeTemplate($this->theme, $this->template);
			$controlView->addContent(array('content' => $content, 'classes' => $classes,
				'links' => $links));
			$controlContent .= $controlView->getDisplay();
		}

		return $controlContent;
	}

	protected function getLinks($pos)
	{
		$links = new HtmlObject('div');
		$links->addClass('dashboard_links');

		$info = $this->controlset->getInfo();
		$link = new Url();
		$link->module = 'Mortar';
		$link->format = 'admin';
		$link->action = 'ControlSettings';
		$link->id = $pos;

		$settings = new HtmlObject('form');
		$settings->property('action', (string) $link);
		$settings->property('method', 'post');

		$basebutton = new HtmlObject('button');
		$basebutton->property('name', 'modify');
		$basebutton->property('type', 'Submit');

		$button = clone($basebutton);
		$button->property('name', 'settings');
		$button->property('value', 'submit');
		$button->wrapAround('Settings');
		$settings->wrapAround($button);		

		$formlink = clone($link);
		$formlink->action = 'ControlModify';

		$form = new HtmlObject('form');
		$form->property('action', (string) $formlink);
		$form->property('method', 'post');

		$item = new HtmlObject('input');
		$item->property('type', 'hidden');
		$item->property('name', 'user');
		$item->property('value', $this->controlset->getUserId());
		$form->wrapAround($item);

		$item = new HtmlObject('input');
		$item->property('type', 'hidden');
		$item->property('name', 'id');
		$item->property('value', $info[$pos]['id']);
		$form->wrapAround($item);

		if($pos != 0) {
			$button = clone($basebutton);
			$button->property('value', 'Move Up');
			$button->wrapAround('Move Up');
			$form->wrapAround($button);
		}

		if($pos != (count($info) - 1)) {
			$button = clone($basebutton);
			$button->property('value', 'Move Down');
			$button->wrapAround('Move Down');
			$form->wrapAround($button);	
		}

		$button = clone($basebutton);
		$button->property('value', 'Remove');
		$button->wrapAround('Remove');
		$form->wrapAround($button);	

		$links->wrapAround($settings);
		$links->wrapAround($form);
		return (string) $links;
	}
}

?>