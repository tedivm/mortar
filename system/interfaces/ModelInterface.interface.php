<?php

Interface ModelInterface
{
	//this is required, but php fails in this respect
//	static public $type;

	public function __construct($locationId = false);

	public function name($name = NULL);

	public function save($saveToLocation = false);

	public function actionLookup($action);

	public function createdDate($date = NULL);

	public function getId();

	// When the $user attribute isn't passed, you should use the active user
	public function isAllowed($action, $user = NULL);

	public function getParent();

}


?>