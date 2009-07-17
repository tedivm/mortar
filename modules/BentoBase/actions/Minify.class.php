<?php

class BentoBaseActionMinify extends ActionBase
{
	static $requiredPermission = 'Read';

	protected $output = '';

	public function logic()
	{
		$query = Query::getQuery();

		if(isset($query['id']))
		{
			$tmp = explode('-', $query['id']);
			$themeName = $tmp[0];
			$tmp = explode('.', $tmp[1]);
			$checksum = $tmp[0];
			$type = $tmp[1];
		}elseif(isset($query['location'])){
			$location = new Location($query['location']);
			$themeName = $location->getMeta('htmlTheme');
			$checksum = 0;
		}

		$theme = new Theme($themeName);
		$minifier = $theme->getMinifier($type);
		$initialCheckSum = $minifier->getInitialChecksum();

		if($initialCheckSum != $checksum)
		{
			$url = $theme->getUrl($type);

			/*
			$url = new Url();
			$url->module = $this->package;
			$url->format = 'direct';
			$url->action = 'Minify';
			$url->id = $themeName . '-' . $initialCheckSum . '.' . $type;
			*/
			$this->ioHandler->addHeader('Location', (string) $url);
			$this->ioHandler->setStatusCode(301);
			return;
		}

		$mimetype = ($type == 'js') ? 'application/x-javascript; charset=utf-8' : 'text/css; charset=utf-8';
		$this->ioHandler->addHeader('Content-Type', $mimetype);
		$this->ioHandler->addHeader('Last-Modified', gmdate('D, d M y H:i:s T', 0));
		$this->ioHandler->addHeader('Expires', gmdate('D, d M y H:i:s T', mktime(0, 0, 0, 0, 0, date('Y') + 20)));
		//var_dump(gmdate('D, d M y H:i:s T', mktime(0, 0, 0, 0, 0, date('Y') + 20)));
		//mktime(0, 0, 0, 0, 0, date('Y') + 20);
		//date('Y',$cd);


		if(defined('DISABLE_MINIFICATION') && DISABLE_MINIFICATION === true)
		{
			$this->output = $minifier->getBaseString();
			return;
		}

		$cache = new Cache('themes', $themeName, 'minification', $type, 'minified');
		$cache->cacheTime = 1209600;
		$minifiedData = $cache->getData();

		if($cache->isStale() || $minifiedData['checksum'] != $initialCheckSum)
		{
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