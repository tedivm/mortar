<?php

class AltamiraTypeDonut extends AltamiraTypePie
{
	protected $pluginFiles = array('jqplot.donutRenderer.min.js');
	protected $renderer = '$.jqplot.DonutRenderer';
}

?>