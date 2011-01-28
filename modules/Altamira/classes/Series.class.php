<?php

class AltamiraSeries
{
	static protected $count = 0;
	protected $data = array();
	protected $tags = array();
	protected $useTags = false;
	protected $useLabels = false;

	protected $title;
	protected $labels= array();
	protected $options = array();
	protected $files = array();
	protected $allowedOptions = array('lineWidth', 'showLine', 'showMarker', 'shadowAngle', 'shadowOffset', 'shadowAlpha', 'shadowDepth', 'pointLabels');

	public function __construct($data, $title = null)
	{
		self::$count++;

		$tagcount = 0;
		foreach($data as $datum) {
			if(is_array($datum) && count($datum) >= 2) {
				$this->useTags = true;
				$this->data[] = array_shift($datum);
				$this->tags[] = array_shift($datum);
			} else {
				$this->data[] = $datum;
				if(count($this->tags) > 0) {
					$this->tags[] = end($this->tags) + 1;
				} else {
					$this->tags[] = 1;
				}
			}
			$tagcount++;
		}

		if(isset($title)) {
			$this->title = $title;
		} else {
			$this->title = 'Series ' . self::$count;
		}
	}

	public function getFiles()
	{
		return $this->files;
	}

	public function setSteps($start, $step)
	{
		$num = $start;
		$this->tags = array();

		foreach($data as $item) {
			$this->tags[] = $num;
			$num += $step;
		}
	}

	public function getData($tags = false)
	{
		if($tags || $this->useTags) {
			$labels = $this->labels;
			if($this->useLabels && (count($labels) > 0)) {
				$useLabels = true;
			} else {
				$useLabels = false;
			}

			$data = array();
			$tags = $this->tags;
			foreach($this->data as $datum) {
				$item = array($datum, array_shift($tags));
				if($useLabels) {
					if(count($labels) === 0) {
						$item[] = null;
					} else {
						$item[] = array_shift($labels);
					}
				}

				$data[] = $item;
			}
			return $data;
		} else {
			return $this->data;
		}
	}

	public function setTitle($title)
	{
		$this->title = $title;
	}

	public function useLabels($labels = array())
	{
		$this->useTags = true;
		$this->useLabels = true;
		$this->options['pointLabels'] = array('show' => true);
		$this->files[] = 'jqplot.pointLabels.min.js';
		$this->labels = $labels;
	}

	public function getTitle()
	{
		return $this->title;
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

		if(isset($this->useLabels) && $this->useLabels)
			$this->options['pointLabels']['show'] = true;

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