<?php
// Developer Constants
define('DEBUG', 0);
// 4,		3,		2,			1,		0
// notices,	info, 	warning, 	error,	none
// strict - E_STRICT with no Bento error displays.
// The higher the number, the more information you get. This constant also controls the php error levels- 0 disables
// error reporting (useful for production environments), while 3 will give all errors and notices. For development
// purposes your best bet would be 2 or 3.
define('STRICT', false);

define('IGNOREPERMISSIONS', false);	//FOR TESTING ONLY!!!!
// This was placed in while testing the permissions code during the early creation phases
// It still comes in handy when testing those things, but if turned on in a development environment
// there would be obvious problems.

define('BENCHMARK', false);
// When enabled the system logs a variety of information. This informaion is saved in the temp/benchmark directory
// As each run of the system generates a new file, it is important not to keep this running on a live system
// This tool is useful in seeing what database queries and cache calls are made, how much memory and cpu time
// the script takes to run, and information about system settings during during that run.


define('DISABLECACHE', false);
// This program is designed to take advantage of caching, and in many cases code was optimized with that in mind.
// Disabling caching is not recommended outside of development, which is why it is not an option in the interface.


define('DEPRECIATION_WARNINGS', false);

define('OUTPUT_COMPRESSION', true);
define('OUTPUT_COMPRESSION_HEADERS', false);
define('CONCISE_HTML', true);

?>