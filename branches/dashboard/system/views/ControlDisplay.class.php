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

			$controlView = new ViewThemeTemplate($this->theme, $this->template);
			$controlView->addContent(array('content' => $content, 'classes' => $classes,
				'links' => $this->getLinks($key)));
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

//		if($pos != 0) {
			
//		}

//		if($pos != (count($info) - 1)) {
		
//		}

		$links->wrapAround($link->getLink('Settings'));
		return (string) $links;
	}
}

?>