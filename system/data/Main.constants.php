<?php
define('FILE_DELIMITER',  (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') ? '/' : '\\');
define('HTTP_DATE', 'D, d M Y H:i:s T');

define('CRON_HANDLER_FAMILY', 'Mortar');
define('CRON_HANDLER_MODULE', 'Core');

define('ERROR_HANDLER_FAMILY', 'Mortar');
define('ERROR_HANDLER_MODULE', 'Rubble');

?>