<?php

class AltamiraActionTestAction extends ActionBase
{
	static $requiredPermission = 'Read';

	public static $settings = array( 'Base' => array( 'headerTitle' => 'Test Action' ) );

	public function viewAdmin($page)
	{
		$chart = new AltamiraMortarChart('testchart');
		$chart->addSeries(new AltamiraSeries(array(1, 2, 3, 4, 5, 6), 'Runs'));
		$chart->setTitle('Chart Title');
		$chart->setType('Bar');
		$chart->setTypeOption('ticks', array('1st Inning', '2nd Inning', '3rd Inning', '4th Inning', '5th Inning', '6th Inning'));
		$chart->setTypeOption('horizontal', true);
		$chart->setTypeOption('varyBarColor', true);
		$chart->setLegend(true, 'outside');

		$chart2 = new AltamiraMortarChart('piechart');
		$chart2->addSeries(new AltamiraSeries(array(array('Pots', 7), array('Pans', 5), array('Spoons', 2), array('Knives', 5), array('Forks', 12)), 'Pie'));
		$chart2->setTitle('Delicious Pie');
		$chart2->setType('Pie');
		$chart2->setLegend(true);
		$chart2->setTypeOption('sliceMargin', 3);
		$chart2->setTypeOption('showDataLabels', true);

		$chart3 = new AltamiraMortarChart('stackchart');
		$chart3->addSeries(new AltamiraSeries(array(3, 4, 2, 7, 4, 9), 'First'));
		$chart3->addSeries(new AltamiraSeries(array(1, 2, 1, 4, 2, 8), 'Second'));
		$chart3->setTitle('Stacked');
		$chart3->setType('Stacked');

		$chart4 = new AltamiraMortarChart('donutchart');
		$chart4->addSeries(new AltamiraSeries(array(array('Pots', 7), array('Pans', 5), array('Spoons', 2), array('Knives', 5), array('Forks', 12)), 'Pie'));
		$chart4->addSeries(new AltamiraSeries(array(array('Pots', 3), array('Pans', 7), array('Spoons', 9), array('Knives', 1), array('Forks', 3)), 'Donuts'));
		$chart4->setTitle('Treats');
		$chart4->setType('Donut');
		$chart4->setLegend(true);
		$chart4->setTypeOption('sliceMargin', 3);

		$seriesA = new AltamiraSeries(array(1, 2, 7, 8, 3, 2), 'Wins');
		$seriesB = new AltamiraSeries(array(7, 3, 4, 1, 7, 4), 'Losses');
		$seriesA->setOption('showMarker', false);
		$seriesB->setOption('markerStyle', 'filledSquare');
		$chart5 = new AltamiraMortarChart('linechart');
		$chart5->addSeries($seriesA);
		$chart5->addSeries($seriesB);
		$chart5->setTitle('Lines');

		return $chart->getDiv() . $chart2->getDiv() . $chart3->getDiv() . $chart4->getDiv() . $chart5->getDiv();
	}

}

?>