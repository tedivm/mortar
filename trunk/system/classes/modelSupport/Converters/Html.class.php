<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage ModelSupport
 */

/**
 * This class returns an HTML representation of the model
 *
 * @package System
 * @subpackage ModelSupport
 */
class ModelToHtml
{
	protected $model;
	protected $template;
	protected $modelDisplay;
	protected $theme;

	/**
	 * The constructor sets the protected vars and prepares the relevant information for Html display which can be output in a 
	 * template or accessed directly
	 *
	 * @param Model $model
	 * @param String $template
	 * @return string
	 */
	public function __construct(Model $model)
	{
		$this->model = $model;
	}

	/**
	 * Provides a template for model data to be inserted into when getOutput() is called
	 *
	 * @param String $template
	 */
	public function useTemplate($template)
	{
		$this->template = $template;
		$this->modelDisplay = new ViewStringTemplate($template);
	}
	
	public function useView($view)
	{
		$this->modelDisplay = $view;
	}

	public function useTheme($theme)
	{
		$this->theme = $theme;
	}

	public function addContent($content)
	{
		return is_array($content) ? $this->modelDisplay->addContent($content) : false;
	}

	/**
	 * This function outputs the loaded model into an HTML string by inserting its values into the used template
	 *
	 * @return string
	 */
	public function getOutput()
	{
		$content = array();

		$modelBox = new TagBoxModel($this->model);
		$content['model'] = $modelBox;

		$envBox = new TagBoxEnv();
		$content['env'] = $envBox;

		$navBox = new TagBoxNav($this->model->getLocation());
		$content['nav'] = $navBox;

		if(isset($this->theme))	{
			$themeBox = new TagBoxTheme($this->theme);
			$content['theme'] = $themeBox;
		}

		$this->modelDisplay->addContent($content);

		$modelOutput = $this->modelDisplay->getDisplay();

		return $modelOutput;
	}
}

?>