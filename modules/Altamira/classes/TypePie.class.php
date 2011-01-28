<?php

class AltamiraTypePie extends AltamiraTypeAbstract
{
	protected $pluginFiles = array('jqplot.pieRenderer.min.js');
	protected $renderer = '$.jqplot.PieRenderer';

	protected $allowedRendererOptions = array('sliceMargin', 'showDataLabels', 'dataLabelPositionFactor', 'dataLabelNudge', 'dataLabels');

	public function getRendererOptions()
	{
		$opts = array();
		foreach($this->allowedRendererOptions as $opt) {
			if(isset($this->options[$opt]))
				$opts[$opt] = $this->options[$opt];
		}
		return $opts;
	}

	public function getUseTags()
	{
		return true;
	}
}

?>