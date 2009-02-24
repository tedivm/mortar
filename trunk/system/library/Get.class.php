<?php

class Get
{

	static public function getArray()
	{
		$queryArray = get_magic_quotes_gpc() ? array_map('stripslashes', $_GET) : $_GET;

		if(isset($queryArray['p']))
		{
			$pathArray = explode('/', str_replace('_', ' ', $queryArray['p']));
			$rootPiece = strtolower($pathArray[0]);

			if($rootPiece == 'admin' || $rootPiece == 'rest')
			{
				if($rootPiece == 'admin')
					$queryArray['format'] = 'admin';

				if($rootPiece == 'rest')
					RequestWrapper::$ioHandler = 'Rest';

				array_shift($pathArray);
				$rootPiece = strtolower($pathArray[0]);
			}

			if($rootPiece == 'module')
			{
				// discard the 'module' tag
				array_shift($pathArray);

				// grab the name, drop it from the path
				$queryArray['module'] = strtolower(array_shift($pathArray));
			}

			if(count($pathArray) > 0)
				$queryArray['pathArray'] = $pathArray;
		}


		switch (strtolower($queryArray['format']))
		{
			case 'xml':
				$queryArray['format'] = 'Xml';
				break;

			case 'rss':
				$queryArray['format'] = 'Rss';
				break;

			case 'json':
				$queryArray['format'] = 'Json';
				break;

			case 'admin':
				$queryArray['format'] = 'Admin';
				break;

			case 'html':
				$queryArray['format'] = 'Html';
				break;

			default:

				unset($queryArray['format']);
				break;
		}

		if(isset($queryArray['action']))
		{
			$queryArray['action'] = preg_replace("/[^a-zA-Z0-9s]/", "", $queryArray['action']);
		}

		return $queryArray;
	}
}

?>