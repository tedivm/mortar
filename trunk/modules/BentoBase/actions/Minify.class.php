<?php

class BentoBaseActionMinify extends ActionBase
{
	static $requiredPermission = 'Read';

	protected $output = '';

	public function logic()
	{
		$query = Query::getQuery();

		// the id should be the file name using the format themeName-checksum.type, like default-65b02d8f.js
		if(isset($query['id']))
		{
			$tmp = explode('-', $query['id']);
			$themeName = $tmp[0];
			$tmp = explode('.', $tmp[1]);
			$checksum = $tmp[0];
			$type = $tmp[1];
		}elseif(isset($query['location'])){

			// if no filename was sent we'll load up the default theme for the site to redirect to the appropriate file
			$location = new Location($query['location']);
			$themeName = $location->getMeta('htmlTheme');
			$checksum = 0;
		}

		$theme = new Theme($themeName);
		$minifier = $theme->getMinifier($type);
		$initialCheckSum = $minifier->getInitialChecksum();

		// if the checksum from the url doesn't match the checksum of the base url
		if($initialCheckSum != $checksum)
		{
			$url = $theme->getUrl($type);
			$this->ioHandler->addHeader('Location', (string) $url);
			$this->ioHandler->setStatusCode(301);
			return;
		}

		$mimetype = ($type == 'js') ? 'application/x-javascript; charset=utf-8' : 'text/css; charset=utf-8';
		$this->ioHandler->addHeader('Content-Type', $mimetype);
		$this->ioHandler->addHeader('Last-Modified', gmdate('D, d M y H:i:s T', 0));
		$this->ioHandler->addHeader('Expires', gmdate('D, d M y H:i:s T', mktime(0, 0, 0, 0, 0, date('Y') + 20)));

		if(defined('DISABLE_MINIFICATION') && DISABLE_MINIFICATION === true)
		{
			$this->output = $minifier->getBaseString();
			return;
		}

		$cache = new Cache('themes', $themeName, 'minification', $type, 'minified');
		// might as well make this huge because the checksum comparison will invalidate it the moment anything changes
		$cache->cacheTime = 31449600;
		$minifiedData = $cache->getData();

		if($cache->isStale() || $minifiedData['checksum'] != $initialCheckSum)
		{
			Cache::clear('themes', $themeName, 'minification', $type, 'url');
			$minifiedData['checksum'] = $initialCheckSum;
			$minifiedData['data'] = $minifier->minifyFiles();
			$cache->storeData($minifiedData);
		}

		$this->output = $minifiedData['data'];
	}

	public function viewDirect()
	{
		return $this->output;
	}

}

?>