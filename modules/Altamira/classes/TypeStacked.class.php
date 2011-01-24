<?php

class AltamiraTypeStacked extends AltamiraTypeAbstract
{

	protected $pluginFiles = array();

	public function getOptions()
	{
		$opts = array();
		$opts['stackSeries'] = true;
		$opts['seriesDefaults'] = array('fill' => true, 'showMarker' => false, 'shadow' => false);

		return $opts;
	}
}

?>