<?php

interface ActionInterface
{
	public function __construct($identifier, $handler);

	public function checkAuth($action = NULL);

	public function start();

}

?>