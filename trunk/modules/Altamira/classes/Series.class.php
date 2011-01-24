<?php

class AltamiraSeries
{
	static protected $count = 0;
	protected $data = array();
	protected $label;
	protected $options = array();
	protected $allowedOptions = array('lineWidth', 'showLine', 'showMarker', 'shadowAngle', 'shadowOffset', 'shadowAlpha', 'shadowDepth');

	public function __construct($data, $label = null)
	{
		self::$count++;
		$this->data = (array) $data;
		if(isset($label)) {
			$this->label = $label;
		} else {
			$this->label = 'Series ' . self::$count;
		}
	}

	public function getData()
	{
		return $this->data;
	}

	public function setLabel($label)
	{
		$this->label = $label;
	}

	public function getLabel()
	{
		return $this->label;
	}

	public function setOption($name, $value)
	{
		$this->options[$name] = $value;
	}

	public function getOptions()
	{
		$opts = array();

		foreach($this->allowedOptions as $opt) {
			if(isset($this->options[$opt]))
				$opts[$opt] = $this->options[$opt];
		}

		$markerOptions = array();
		if(isset($this->options['markerStyle']))
			$markerOptions['style'] = $this->options['markerStyle'];
		if(isset($this->options['markerSize']))
			$markerOptions['size'] = $this->options['markerSize'];

		if(count($markerOptions) != 0)
			$opts['markerOptions'] = $markerOptions;

		return $opts;
	}
}

?>