<?php

abstract class AltamiraTypeAbstract
{
	protected $pluginFiles;
	protected $renderer;
	protected $options;

	public function getFiles()
	{
		return $this->pluginFiles;
	}

	public function getRenderer()
	{
		if(isset($this->renderer)) {
			return '#' . $this->renderer . '#';
		} else {
			return null;
		}
	}

	public function getOptions()
	{
		return array();
	}

	public function getSeriesOptions()
	{
		return array();
	}

	public function getRendererOptions()
	{
		return array();
	}

	public function getUseTags()
	{
		return false;
	}

	public function setOption($name, $value)
	{
		$this->options[$name] = $value;

		return $this;
	}
}

?>