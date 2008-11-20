<?php
$config = Config::getInstance();
if(!class_exists('HtmlHelper', false))
	include $config['path']['engines'] . 'Html.engineHelper.php';

class AdminHelper extends HtmlHelper 
{
	
}


?>