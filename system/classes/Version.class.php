<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 */

/**
 * This class turns a version into a string and back, and compares to versions
 *
 * @package MainClasses
 */
class Version
{
	/**
	 * Major version number
	 *
	 * @var int
	 */
	public $major;

	/**
	 * Minor version number
	 *
	 * @var int
	 */
	public $minor;

	/**
	 * Micro version number
	 *
	 * @var int
	 */
	public $micro;

	/**
	 * Release type (alpha, beta, release candidate, release)
	 *
	 * @var string
	 */
	public $releaseType;

	/**
	 * Release version number
	 *
	 * @var int
	 */
	public $releaseVersion;


	/**
	 * Compares to versions
	 *
	 * @param Version $version
	 * @return int If the passed version is newer, we return 1, the same 0 and older -1
	 */
	public function compare(Version $version)
	{
		// if this is older, -1
		// if the same, 0
		// if this is newer, 1

		if($this->major > $version->major)
		{
			return 1;
		}elseif($this->major < $version->major){
			return -1;
		}

		if(!is_int($this->minor))
			$this->minor = 0;


		if(!is_int($version->minor))
			$version->minor = 0;

		if($this->minor > $version->minor)
		{
			return 1;
		}elseif($this->minor < $version->minor){
			return -1;
		}


		if(!is_int($this->micro))
			$this->micro = 0;


		if(!is_int($version->micro))
			$version->micro = 0;

		if($this->micro > $version->micro)
		{
			return 1;
		}elseif($this->micro < $version->micro){
			return -1;
		}

		switch ($this->releaseType) {
			case 'Alpha':
				$thisType = -3;
				break;

			case 'Beta':
				$thisType = -2;
				break;

			case 'ReleaseCandidate':
				$thisType = -1;
				break;

			case 'Release':
			default:
				$thisType = 1;
				break;
		}


		switch ($version->releaseType) {
			case 'Alpha':
				$compareType = -3;
				break;

			case 'Beta':
				$compareType = -2;
				break;

			case 'ReleaseCandidate':
				$compareType = -1;
				break;

			case 'Release':
			default:
				$compareType = 1;
				break;
		}

		if($thisType > $compareType)
		{
			return 1;
		}elseif($thisType < $compareType){
			return -1;
		}

		if($thisType > 0)
		{
			if($this->releaseVersion > $version->releaseVersion)
			{
				return 1;
			}elseif($this->releaseVersion < $version->releaseVersion){
				return -1;
			}
		}

		return 0;
	}

	/**
	 * Returns a string representation of the version
	 *
	 * @return string
	 */
	public function __toString()
	{
		if($this->micro > 0)
		{
			$output .= '.' . $this->micro;
		}

		if($this->minor > 0 || strlen($output) > 0)
		{
			$output = '.' . (($this->minor > 0) ? $this->minor : '0') . $output;
		}

		$output = (($this->major > 0) ? $this->major : '0') . $output;

		if(strlen($this->releaseType) > 0)
		{
			$output .= ' ' . $this->releaseType;

			if(strlen($this->releaseVersion) > 0)
			{
				$output .= ' ' . $this->releaseVersion;
			}
		}

		return $output;
	}

	/**
	 * Takes a string and populates this information
	 *
	 * @param string $version
	 */
	public function fromString($version)
	{

		$versionArray = explode('.', $version);

		$lastPiece =array_pop($versionArray);

		if(!is_int($lastPiece) && is_string($lastPiece))
		{
			$lastPieceArray = explode(' ', $lastPiece);
			$versionArray[] = $lastPieceArray[0];
			$this->releaseType = $lastPieceArray[1];
			$this->releaseVersion = $lastPieceArray[2];
		}


		$this->major = (int) $versionArray[0];

		$this->minor = (int) $versionArray[1];

		$this->micro = (int) $versionArray[2];

	}

}

?>
