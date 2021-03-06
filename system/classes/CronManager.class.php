<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Cron
 */

/**
 * This class is used to manage Cron jobs. It can be used to add new jobs, adjust job times, and enable or disble an
 * action.
 *
 * @package System
 * @subpackage Cron
 */
class CronManager
{
	/**
	 * This function is used to register or change the time of a cron job.
	 *
	 * @param string|int $module
	 * @param string $action
	 * @param int|null $minutesBetweenRequests
	 * @param string|null $start HH:MM
	 * @param string|null $end HH:MM
	 * @param int|array $daysOfWeek 0-6 Arrays assign multiple days
	 * @param int|array $daysOfMonth Arrays assign multiple days
	 * @return bool
	 */
	static function registerJob($action, $source, $type = 'module', $minutesBetweenRequests = null, $start = null,
										$end = null, $daysOfWeek = null, $daysOfMonth = null)
	{
		if(!($cronJobOrm = self::getOrm($action, $source, $type)))
			return false;

		// If it exists use it then update it.
		$cronJobOrm->select();

		$cronJobOrm->jobPid = 1;
		$cronJobOrm->minutesBetweenRequests = (isset($minutesBetweenRequests)) ? $minutesBetweenRequests : 0;

		if(isset($start))
		{
			$cronJobOrm->restrictTimeStart = $start;
		}else{
			$cronJobOrm->querySet('restrictTimeStart', 'NULL');
		}

		if(isset($end))
		{
			$cronJobOrm->restrictTimeEnd = $end;
		}else{
			$cronJobOrm->querySet('restrictTimeEnd', 'NULL');
		}

		if(isset($daysOfWeek))
		{
			$daysOfWeekSql = '';
			if(is_array($daysOfWeek))
			{
				sort($daysOfWeek);
				foreach($daysOfWeek as $day)
				{
					if(is_numeric($day) && $day < 7)
						$daysOfWeekSql .= $day . ',';
				}
			}elseif(is_numeric($daysOfWeek)){
				$daysOfWeekSql = $daysOfWeek;
			}

			$daysOfWeekSql = rtrim($daysOfWeekSql, ',');
			if(strlen($daysOfWeekSql) > 0)
				$cronJobOrm->restrictTimeDayOfWeek = $daysOfWeekSql;
		}else{
			$cronJobOrm->querySet('restrictTimeDayOfWeek', 'NULL');
		}

		if(isset($daysOfMonth))
		{
			$daysOfMonthSql = '';
			if(is_array($daysOfMonth))
			{
				sort($daysOfMonth);
				foreach($daysOfMonth as $day)
				{
					if(is_numeric($day) && $day < 32)
					{
						if($day < 10)
							$daysOfMonthSql .= '0';

						$daysOfMonthSql .= $day . ',';
					}
				}
			}elseif(is_numeric($daysOfMonth)){
				$daysOfMonthSql = $daysOfMonth;
			}

			$daysOfMonthSql = rtrim($daysOfMonthSql, ',');
			if(strlen($daysOfMonthSql) > 0)
				$cronJobOrm->restrictTimeDayOfMonth = $daysOfMonthSql;
		}else{
			$cronJobOrm->querySet('restrictTimeDayOfMonth', 'NULL');
		}

		return $cronJobOrm->save();
	}


	/**
	 * This function disables a cron job action.
	 *
	 * @param string $module
	 * @param string $action
	 * @return bool
	 */
	static function disableJob($action, $source, $type = 'module')
	{
		if(!($cronJobOrm = self::getOrm($action, $source, $type)))
			return false;

		// If it exists use it then update it.
		$cronJobOrm->select();

		$cronJobOrm->jobPid = 0;
		return $cronJobOrm->save();
	}

	/**
	 * This function enables a cron job action.
	 *
	 * @param string $module
	 * @param string $action
	 * @return bool
	 */
	static function enableJob($action, $source, $type = 'module')
	{
		if(!($cronJobOrm = self::getOrm($action, $source, $type)))
			return false;

		// If it exists use it then update it.
		if(!$cronJobOrm->select())
			return false;

		$cronJobOrm->jobPid = 1;
		return $cronJobOrm->save();
	}

	/**
	 * This function makes sure the module and action names are propery before loading them into an ORM object, which
	 * is returned.
	 *
	 * @param string $module
	 * @param string $action
	 * @return ObjectRelationshipMapper
	 */
	static protected function getOrm($action, $source, $type = 'module')
	{
		$cronJobOrm = new ObjectRelationshipMapper('cronJobs');

		if($type == 'module' && is_numeric($source))
		{
			$packageInfo = PackageInfo::loadById($source);
			$moduleId = $packageInfo->getId();

			if(!is_numeric($moduleId) || $moduleId < 1)
				return false;

			if(!$packageInfo->getActions($action))
				return false;

			$cronJobOrm->moduleId = $source;

		}elseif($type == 'location'){

			if(!is_numeric($source) || $source < 1)
				return false;

			$location = Location::getLocation($source);
			$locationId = $location->getId();
			$model = $location->getResource();

			if($model->getAction($action) == false)
				return false;

			$cronJobOrm->locationId = $locationId;
		}else{
			return false;
		}

		$cronJobOrm->actionName = $action;

		return $cronJobOrm;
	}

}

?>