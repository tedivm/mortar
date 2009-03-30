<?php

class Get
{

	static public function getArray()
	{
		$queryArray = get_magic_quotes_gpc() ? array_map('stripslashes', $_GET) : $_GET;
		return $queryArray;
	}









}

?>