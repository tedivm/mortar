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
			$controlView->addContent(array('content' => $content, 'classes' => $classes));
			$controlContent .= $controlView->getDisplay();
		}

		return $controlContent;
	}
}

?>