<?php

class AltamiraActionGraphingDemo extends ActionBase
{
	static $requiredPermission = 'Read';

	public static $settings = array( 'Base' => array( 'headerTitle' => 'Graphing Demo' ) );

	public function viewAdmin($page)
	{
		$chart = new AltamiraMortarChart('chart1');
		$chart->addSeries(new AltamiraSeries(array(2, 8, 5, 3, 8, 9, 7, 8, 4, 2, 1, 6), 'Sales'))->
			addSeries(new AltamiraSeries(array(7, 3, 7, 8, 2, 3, 1, 2, 5, 7, 8, 3), 'Returns'))->
			setTitle('Basic Line Chart')->
			setAxisOptions('y', 'formatString', '$%d')->
			setAxisOptions('x', 'tickInterval', 1)->
			setAxisOptions('x', 'min', 0)->
			setLegend(true);

		$output = $chart->getDiv();
		$output .= '<code>
		$chart = new AltamiraMortarChart(\'chart1\');
		$chart->addSeries(new AltamiraSeries(array(2, 8, 5, 3, 8, 9, 7, 8, 4, 2, 1, 6), \'Sales\'))->
			addSeries(new AltamiraSeries(array(7, 3, 7, 8, 2, 3, 1, 2, 5, 7, 8, 3), \'Returns\'))->
			setTitle(\'Basic Line Chart\')->
			setAxisOptions(\'y\', \'formatString\', \'$%d\')->
			setAxisOptions(\'x\', \'tickInterval\', 1)->
			setAxisOptions(\'x\', \'min\', 0)->
			setLegend(true);</code>';

		$chart2 = new AltamiraMortarChart('chart2');
		$series = new AltamiraSeries(
				array(	array('1/4/1990', 750), 
					array('2/27/1991', 427), 
					array('1/6/1994', 990),
					array('8/6/1994', 127),
					array('12/25/1995', 325)), 'Measured Readings');
		$series->useLabels(array('a', 'b', 'c', 'd', 'e'))->
			setLabelSetting('location', 'w')->
			setLabelSetting('xpadding', 8)->
			setLabelSetting('ypadding', 8);
		$chart2->setTitle('Line Chart With Highlights and Labels')->
			addSeries($series)->
			useDates()->
			useHighlighting();

		$output .= $chart2->getDiv();

		$output .= '<code>
		$chart2 = new AltamiraMortarChart(\'chart2\');
		$chart2->setTitle(\'Line Chart With Dates and Highlights\')->
			addSeries(new AltamiraSeries(
				array(	array(\'1/4/1990\', 750), 
					array(\'2/27/1991\', 427), 
					array(\'1/6/1994\', 990),
					array(\'8/6/1994\', 127),
					array(\'12/25/1995\', 325)), \'Measured Readings\'))->
			useDates()->
			useHighlighting();</code>';

		$chart3 = new AltamiraMortarChart('chart3');
		$seriesA = new AltamiraSeries(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10), 'First');
		$seriesB = new AltamiraSeries(array(1, 10, 2, 9, 3, 8, 4, 7, 5, 6), 'Second');
		$seriesC = new AltamiraSeries(array(10, 7, 6, 5, 3, 1, 3, 5, 6, 7), 'Third');

		$seriesA->setOption('showMarker', false)->
			setOption('lineWidth', 8);
		$seriesB->setOption('markerStyle', 'filledSquare')->
			setOption('showLine', false);
		$seriesC->setOption('lineWidth', 1)->
			setOption('markerSize', 15)->
			setOption('markerStyle', 'diamond');

		$chart3->setTitle('Line Chart With Custom Formats And Zoom (drag to zoom, double-click to reset)')->
			addSeries($seriesA)->
			addSeries($seriesB)->
			addSeries($seriesC)->
			useZooming();

		$output .= $chart3->getDiv();

		$output .= '<code>
		$chart3 = new AltamiraMortarChart(\'chart3\');
		$seriesA = new AltamiraSeries(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10), \'First\');
		$seriesB = new AltamiraSeries(array(1, 10, 2, 9, 3, 8, 4, 7, 5, 6), \'Second\');
		$seriesC = new AltamiraSeries(array(10, 7, 6, 5, 3, 1, 3, 5, 6, 7), \'Third\');

		$seriesA->setOption(\'showMarker\', false)->
			setOption(\'lineWidth\', 8);
		$seriesB->setOption(\'markerStyle\', \'filledSquare\')->
			setOption(\'showLine\', false);
		$seriesC->setOption(\'lineWidth\', 1)->
			setOption(\'markerSize\', 15)->
			setOption(\'markerStyle\', \'diamond\');

		$chart3->setTitle(\'Line Chart With Custom Formats And Zoom (drag to zoom, double-click to reset)\')->
			addSeries($seriesA)->
			addSeries($seriesB)->
			addSeries($seriesC)->
			useZooming();</code>';

		$chart4 = new AltamiraMortarChart('chart4');
		$chart4->setTitle('Horizontal Bar Chart')->
			addSeries(new AltamiraSeries(array(1, 4, 8, 2, 1, 5), 'Runs'))->
			addSeries(new AltamiraSeries(array(3, 3, 5, 4, 2, 6), 'Walks'))->
			setType('Bar')->
			setTypeOption('horizontal', true)->
			setAxisTicks('y', array('1st Inning', '2nd Inning', '3rd Inning', '4th Inning', '5th Inning', '6th Inning'))->
			setLegend(true, 'se', 5, 5);

		$output .= $chart4->getDiv();

		$output .= '<code>
		$chart4 = new AltamiraMortarChart(\'chart4\');
		$chart4->setTitle(\'Horizontal Bar Chart\')->
			addSeries(new AltamiraSeries(array(1, 4, 8, 2, 1, 5), \'Runs\'))->
			addSeries(new AltamiraSeries(array(3, 3, 5, 4, 2, 6), \'Walks\'))->
			setType(\'Bar\')->
			setTypeOption(\'horizontal\', true)->
			setAxisTicks(\'y\', array(\'1st Inning\', \'2nd Inning\', \'3rd Inning\', \'4th Inning\', \'5th Inning\', \'6th Inning\'))->
			setLegend(true, \'se\', 5, 5);</code>';

		$chart5 = new AltamiraMortarChart('chart5');
		$chart5->setTitle('Pie Chart')->
			addSeries(new AltamiraSeries(array(array('Pots', 7), array('Pans', 5), array('Spoons', 2), array('Knives', 5), array('Forks', 12)), 'Utensils'))->
			setType('Pie')->
			setLegend(true);

		$output .= $chart5->getDiv();

		$output .= '<code>
		$chart5 = new AltamiraMortarChart(\'chart5\');
		$chart5->setTitle(\'Pie Chart\')->
			addSeries(new AltamiraSeries(array(array(\'Pots\', 7), array(\'Pans\', 5), array(\'Spoons\', 2), array(\'Knives\', 5), array(\'Forks\', 12)), \'Utensils\'))->
			setType(\'Pie\')->
			setLegend(true);</code>';

		$chart6 = new AltamiraMortarChart('chart6');
		$chart6->setTitle('Donut Chart With Custom Colors And Labels')->
			addSeries(new AltamiraSeries(array(array('Metals', 3), array('Plastics', 5), array('Wood', 2), array('Glass', 7), array('Paper', 9)), 'Internal'))->
			addSeries(new AltamiraSeries(array(array('Metals', 4), array('Plastics', 2), array('Wood', 5), array('Glass', 4), array('Paper', 12)), 'External'))->
			setSeriesColors(array('#dd3333', '#d465f1', '#aa2211', '#3377aa', '#6699bb', '#9933aa'))->
			setType('Donut')->
			setLegend(true)->
			setTypeOption('sliceMargin', 3)->
			setTypeOption('showDataLabels', true);

		$output .= $chart6->getDiv();

		$output .= '<code>
		$chart6 = new AltamiraMortarChart(\'chart6\');
		$chart6->setTitle(\'Donut Chart With Custom Colors And Labels\')->
			addSeries(new AltamiraSeries(array(array(\'Metals\', 3), array(\'Plastics\', 5), array(\'Wood\', 2), array(\'Glass\', 7), array(\'Paper\', 9)), \'Internal\'))->
			addSeries(new AltamiraSeries(array(array(\'Metals\', 4), array(\'Plastics\', 2), array(\'Wood\', 5), array(\'Glass\', 4), array(\'Paper\', 12)), \'External\'))->
			setSeriesColors(array(\'#dd3333\', \'#d465f1\', \'#aa2211\', \'#3377aa\', \'#6699bb\', \'#9933aa\'))->
			setType(\'Donut\')->
			setLegend(true)->
			setTypeOption(\'sliceMargin\', 3)->
			setTypeOption(\'showDataLabels\', true);</code>';

		$chart7 = new AltamiraMortarChart('chart7');
		$chart7->addSeries(new AltamiraBubbleSeries(
			array( 	array(4, 7, 5, 'Screws'), 
				array(5, 3, 6, 'Nails'), 
				array(4, 5, 7, 'Bolts'), 
				array(3.5, 4, 6, 'Nuts'),
				array(3, 2, 5, 'Washers'),
				array(4, 1, 5, 'Pliers'),
				array(4.5, 6, 6, 'Hammers'))))->
			setTitle('Bubble Chart')->
			setType('Bubble')->
			setTypeOption('bubbleAlpha', .5)->
			setTypeOption('highlightAlpha', .7);

		$output .= $chart7->getDiv();

		$output .= '<code>
		$chart7 = new AltamiraMortarChart(\'chart7\');
		$chart7->addSeries(new AltamiraBubbleSeries(
			array( 	array(4, 7, 5, \'Screws\'), 
				array(5, 3, 6, \'Nails\'), 
				array(4, 5, 7, \'Bolts\'), 
				array(3.5, 4, 6, \'Nuts\'),
				array(3, 2, 5, \'Washers\'),
				array(4, 1, 5, \'Pliers\'),
				array(4.5, 6, 6, \'Hammers\'))))->
			setTitle(\'Bubble Chart\')->
			setType(\'Bubble\')->
			setTypeOption(\'bubbleAlpha\', .5)->
			setTypeOption(\'highlightAlpha\', .7);</code>';

		return $output;
	}

	public function viewHtml($page)
	{
		return $this->viewAdmin($page);
	}

}

?>