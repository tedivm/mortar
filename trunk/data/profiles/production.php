<?php
// Developer Constants
BootStrapper::define('DEBUG', 0);
// 4,		3,		2,			1,		0
// notices,	info, 	warning, 	error,	none
// strict - E_STRICT with no Mortar error displays.
// The higher the number, the more information you get. This constant also controls the php error levels- 0 disables
// error reporting (useful for production environments), while 3 will give all errors and notices. For development
// purposes your best bet would be 2 or 3.
BootStrapper::define('STRICT', false);
BootStrapper::define('ERROR_LOGGING', 3);
BootStrapper::define('IGNOREPERMISSIONS', false);	//FOR TESTING ONLY!!!!
// This was placed in while testing the permissions code during the early creation phases
// It still comes in handy when testing those things, but if turned on in a development environment
// there would be obvious problems.

BootStrapper::define('BENCHMARK', false);
// When enabled the system logs a variety of information. This informaion is saved in the temp/benchmark directory
// As each run of the system generates a new file, it is important not to keep this running on a live system
// This tool is useful in seeing what database queries and cache calls are made, how much memory and cpu time
// the script takes to run, and information about system settings during during that run.


BootStrapper::define('DISABLECACHE', false);
// This program is designed to take advantage of caching, and in many cases code was optimized with that in mind.
// Disabling caching is not recommended outside of development, which is why it is not an option in the interface.


BootStrapper::define('DEPRECIATION_WARNINGS', false);
// When this is enabled functions and methods that are being depreciated throw a warning.

BootStrapper::define('OUTPUT_COMPRESSION', true);
// This enabled output compression for HTTP connections, using the deflate and gzip methods
// DO NOT ENABLE WITH DEBUGGING > 0! Debugging information is not compressed, and the combination of compressed
// and not compressed text makes it impossible to uncompress


BootStrapper::define('OUTPUT_COMPRESSION_HEADERS', false);
// When enabled the system will send out headers showing the compression results, so you can see how much
// the document size has shrunk

BootStrapper::define('CONCISE_HTML', true);
// This setting will tell the system not to add additional whitespace and comments to the autogenerated html tags
// in order to lower the document size.


BootStrapper::define('CLEAN_TEMPLATES', true);
// This constant lets us stop templates from cleaning out tags, so we can see which tags were not replaced with content.

BootStrapper::define('XDEBUG_PROFILE', false);
// This setting appends an attribute to enable xdebug profiling to all URLs

BootStrapper::define('DISABLE_MINIFICATION', false);
// When set to true, the system will output JS and CSS in its original form, rather than minified.

BootStrapper::define('REBUILD_TEMPLATES', false);
// When set to true, any template file whose source has changed on disk will be recompiled before it's rendered.

?>