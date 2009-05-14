<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage RequestWrapper
 */

/**
 * This class generates some statistics about the current request and saves it to a file
 *
 * @package System
 * @subpackage RequestWrapper
 */
class RequestStatistics
{
	protected $requestValues = array();

	public function __construct()
	{
		$this->loadInformation();
	}

	protected function loadInformation()
	{
		$runTime = microtime(true) - START_TIME;
		$startdat = getrusage();
		$totalProcTime = $startdat["ru_utime.tv_usec"] - START_PROCESS_TIME;

		$info['System Stats']['RunTime'] = $runTime;
		$info['System Stats']['ProcessorTime'] = $totalProcTime;

		$info['System Stats']['Memory Usage'] = (int) (memory_get_usage() / 1024) . 'k';
		$info['System Stats']['Peak Memory Usage'] = (int) (memory_get_peak_usage() / 1024) . 'k';

		$currentSite = ActiveSite::getSite();
		$siteLocation = $currentSite->getLocation();

		$info['Current Site']['Name'] = $siteLocation->getname();
		$info['Current Site']['Site Link'] = ActiveSite::getLink();
		$info['Current Site']['Id'] = $currentSite->getId();
		$info['Current Site']['Current Page'] = Query::getUrl();

		$user = ActiveUser::getInstance();
		$info['Active User']['Id'] = $user->getId();
		$info['Active User']['Name'] = $user->getName();


		$info['Cache']['Calls Count'] = Cache::$cacheCalls;
		$info['Cache']['Returns'] = Cache::$cacheReturns;
		$info['Cache']['Total Results'] = count(Cache::$memStore);



		$memStore = array_keys(Cache::$memStore);
		asort($memStore);
		$info['Cache']['Calls'] = array_values($memStore);


	//	$info['Current Site']['name'] = $currentSite->getName();

		$info['MySql']['Query Count'] = MysqlBase::$queryCount;
		$info['MySql']['Queries'] = MysqlBase::$queryArray;
		$this->requestValues = $info;
	}

	public function saveToFile()
	{
		$config = Config::getInstance();
		if(!$config->error)
		{
			$fileName = microtime(true) . '.txt';
			$benchmarkPath = $config['path']['temp'] . 'benchmarks/';

			if(!is_dir($benchmarkPath))
				mkdir($benchmarkPath, 0755, true);

			return file_put_contents($config['path']['temp'] . 'benchmarks/' . $fileName, (string) $this);
		}
		return false;
	}


	public function __toString()
	{
		return $this->arrayToString($this->requestValues);
	}



	public function arrayToString($array, $level = 0)
	{
		$tab = str_repeat('   ', $level);
		$string = PHP_EOL;

		foreach($array as $name => $value)
		{
			$string .= $tab . $name . ': ';
			if(is_array($value))
			{
				$string .= $this->arrayToString($value, $level + 1) . PHP_EOL;
			}else{
				$string .= $value . PHP_EOL;
			}
		}
		return $string;
	}



}

?>