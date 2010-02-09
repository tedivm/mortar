<?php

class MortarActionMinify extends ActionBase
{
	static $requiredPermission = 'Read';

	protected $output = '';

	protected $processingTheme;

	public function logic()
	{
		$query = Query::getQuery();

		// the id should be the file name using the format themeName-checksum.type, like default-65b02d8f.js
		if(isset($query['id']))
		{
			$tmp = explode('-', $query['id']);
			$themeName = $tmp[0];
			$requestedChecksum = $tmp[1];

		}elseif(isset($query['location'])){

			// if no filename was sent we'll load up the default theme for the site to redirect to the appropriate file
			$location = new Location($query['location']);
			$themeName = $location->getMeta('htmlTheme');
			$checksum = 0;
		}

		$type = strtolower($query['format']);

		$theme = new Theme($themeName);
		$this->processingTheme = $theme;
		$minifier = $theme->getMinifier($type);

		$actualCheckSum = $minifier->getInitialChecksum();

		// if the checksum from the url doesn't match the checksum of the base url
		if(!isset($requestedChecksum) || $actualCheckSum != $requestedChecksum)
		{
			$url = $theme->getUrl($type);
			if(isset($query['id']) && $url->id == $query['id'])
			{
				Cache::clear('themes', $themeName, 'minification', $type);
				$url = $theme->getUrl($type);
			}

			if(isset($query['raw']) && $query['raw'] == true)
				$url->raw = true;

			$this->ioHandler->addHeader('Location', (string) $url);
			$this->ioHandler->setStatusCode(301);
			return;
		}

		$mimetype = ($type == 'js') ? 'application/x-javascript; charset=utf-8' : 'text/css; charset=utf-8';
		$this->ioHandler->addHeader('Content-Type', $mimetype);
		$this->ioHandler->addHeader('Last-Modified', gmdate(HTTP_DATE, 0));
		$this->ioHandler->addHeader('Expires', gmdate(HTTP_DATE, mktime(0, 0, 0, 0, 0, date('Y') + 20)));



		if((defined('DISABLE_MINIFICATION') && DISABLE_MINIFICATION === true)
			|| (isset($query['raw']) && $query['raw'] == true))
		{
			$this->output = $minifier->getBaseString();
			return;
		}

		$cache = new Cache('themes', $themeName, 'minification', $type, 'minified');
		// might as well make this huge because the checksum comparison will invalidate it the moment anything changes
		$cache->cacheTime = 31449600;
		$minifiedData = $cache->getData();

		if($cache->isStale() || $minifiedData['checksum'] != $requestedChecksum)
		{
			Cache::clear('themes', $themeName, 'minification', $type, 'url');
			$minifiedData['checksum'] = $actualCheckSum;
			$minifiedData['data'] = $minifier->minifyFiles();
			$cache->storeData($minifiedData);
		}

		$rawUrl = new Url();
		$rawUrl->action = 'Minify';
		$rawUrl->id = $query['id'];
		$rawUrl->format = $type;
		$rawUrl->module = 'Mortar';
		$rawUrl->raw = true;

		$output = '/* Raw Source: ' . (string) $rawUrl . ' */' . PHP_EOL;
		$output .= $minifiedData['data'];
		$this->output = $output;
	}

	public function viewCss()
	{
		return $this->output;
	}

	public function viewJs()
	{
		return $this->output;
	}

	public function viewDirect()
	{
		return $this->output;
	}
}

?>