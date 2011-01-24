<?php

class AltamiraChart
{
	protected $name;
	protected $handler;

	protected $types = array();
	protected $options = array();
	protected $series = array();
	protected $files = array();

	public function __construct($name = null)
	{
		if(isset($name))
			$this->name = $name;
	}

	public function setTitle($title)
	{
		$this->options['title'] = $title;
	}

	public function setType($type, $series = null)
	{
		if(isset($series) && isset($this->series[$series])) {
			$series = $this->series[$series];
			$label = $series->getLabel();
		} else {
			$label = 'default';
		}
		
		$className = 'AltamiraType' . ucwords($type);
		if(class_exists($className))
			$this->types[$label] = new $className();
	}

	public function setTypeOption($name, $option, $series = null)
	{
		if(isset($series)) {
			$label = $series;
		} else {
			$label = 'default';
		}

		if(isset($this->types[$label]))
			$this->types[$label]->setOption($name, $option);
	}

	public function setLegend($on = true, $location = 'ne', $x = 0, $y = 0)
	{
		if(!$on) {
			unset($this->options['legend']);
		} else {
			$legend = array();
			$legend['show'] = true;
			if($location == 'outside') {
				$legend['placement'] = $location;
			} else {
				$legend['location'] = $location;
			}
			if($x != 0)
				$legend['xoffset'] = $x;
			if($y != 0)
				$legend['yoffset'] = $y;
			$this->options['legend'] = $legend;
		}
	}

	public function addSeries(AltamiraSeries $series)
	{
		$this->series[$series->getLabel()] = $series;
	}

	public function getPluginList()
	{
		return array();
	}

	public function getDiv($height = 400, $width = 500)
	{
		return '<div class="jqPlot" id="' . $this->name . 
			'" style="height:'. $height . 'px; width:' . $width . 'px;"></div>';
	}

	public function getFiles()
	{
		foreach($this->types as $type) {
			$this->files = array_merge($this->files, $type->getFiles());
		}

		return $this->files;
	}

	public function getScript()
	{
		$output  = '$(document).ready(function(){';
		$output .= '$.jqplot.config.enablePlugins = true;';

		$num = 0;
		$vars = array();
		foreach($this->series as $series) {
			$num++;
			$data = $series->getData();
			
			$varname = 'plot_' . $this->name . '_' . $num;
			$vars[] = '#' . $varname . '#';
			$output .= $varname . ' = ' . $this->makeJSArray($data) . ';';
		}

		$output .= 'plot = $.jqplot("' . $this->name . '", ';
		$output .= $this->makeJSArray($vars);
		$output .= ', ';
		$output .= $this->getOptionsJS();
		$output .= ');';
		$output .= '});';

		return $output;
	}

	protected function runSeriesOptions()
	{
		if(isset($this->types['default'])) {
			$defaults = array();
			$renderer = $this->types['default']->getRenderer();
			if(isset($renderer))
				$defaults['renderer'] = $renderer;
			$defaults['rendererOptions'] = $this->types['default']->getRendererOptions();
			if(count($defaults['rendererOptions']) == 0)
				unset($defaults['rendererOptions']);
			$this->options['seriesDefaults'] = $defaults;
		}

		$seriesOptions = array();
		foreach($this->series as $series) {
			$opts = $series->getOptions();
			$label = $series->getLabel();
			if(isset($this->types[$label])) {
				$type = $this->types[$label];
				$opts['renderer'] = $type->getRenderer();
				array_merge($opts, $type->getSeriesOptions());
			}
			$opts['label'] = $label;
			$seriesOptions[] = $opts;
		}
		$this->options['series'] = $seriesOptions;
	}

	protected function runTypeOptions()
	{
		if(isset($this->types['default'])) {
			$this->options = array_merge($this->options, $this->types['default']->getOptions());
		}
	}

	protected function getOptionsJS()
	{
		$this->runSeriesOptions();
		$this->runTypeOptions();
		return $this->makeJSArray($this->options);
	}

	protected function makeJSArray($array)
	{
		$options = json_encode($array);
		return preg_replace('/"#(.*?)#"/', '$1', $options);
	}
}

?>