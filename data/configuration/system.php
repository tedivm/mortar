<?php
/*
 * This file can be used by admins to override the system defined constants. If file will not be changed during updates.
 * By default this file has nothing but comments and can safely be deleted.
 *
 */


/* Runtime Profile - this lets you pick which profile the system should fall back on. Any option not defined in this
 * file will instead be defined by the selected profile. Current profiles include Production, Development, Design and
 * Testing. The options in this file are based off of the default profile, Production. */
//define('RUNTIME_PROFILE', 'production');


/*
 * Error Handling
 *
 * This sections defines how errors, including php errors, are displayed and logged. For options requiring a severity
 * a number from the list below is expected, with higher levels including all those below.
 *
 * 5 - Info
 * 4 - User Errors (failed logins, 404s, etc)
 * 3 - Notices
 * 2 - Warnings
 * 1 - Errors
 * 0 - None
 *
 */

/* This specifies what severity of errors are outputted to the user, with the default being none. */
//define('DEBUG', 0);

/* This specifies what types of errors are logged to the database. */
//define('ERROR_LOGGING', 3);

/* This can be used to enable php's E_STRICT which will be sent the user regardless of DEBUG level */
//define('STRICT', false);

/* When enabled depreciated functions will display/log errors when called. */
//define('DEPRECIATION_WARNINGS', false);


/*
 * Development
 *
 * This sections allows admins to override settings related to development and testing.
 *
 */

/* Enables the creation of "benchmark" files in the temp folder containing performance data useful to testing. */
//define('BENCHMARK', false);

/* Added the "XDEBUG_PROFILE" get attribute to generated URLs to enable profiling when xdebug is present. */
//define('XDEBUG_PROFILE', false);

/* When disabled all html generated by the system has whitespace and comments added for readablity. */
//define('CONCISE_HTML', true);


/*
 * Performance
 *
 * This sections allows admins to override settings related to performance. These should not be altered on production
 * systems. Additional useful options may be found in Development.
 *
 */

/* Allows output compression to be disabled */
//define('OUTPUT_COMPRESSION', true);

/* Sends HTTP headers with compression ratio and time spent retrieving or compressing the content. */
//define('OUTPUT_COMPRESSION_HEADERS', false);


/*
 * Caching
 *
 * This section deals with caching related issues, including the template cache.
 *
 */

/* When disabled the system performs faster but template changes won't invalidate the template cache. */
//define('REBUILD_TEMPLATES', false);

/* Disables minification of javascript and css. Extremely useful when caching is disabled. */
//define('DISABLE_MINIFICATION', false);

/* Disables the caching system, including the script-only cache. Not recommended for production systems at all. */
//define('DISABLECACHE', true);

/* Sets the caching system to use only memory caching. Only functions when caching is not disabled. */
//define('CACHE_SETMEMONLY', false);

/*
 * Binaries
 *
 * If a binary file the system needs is not in the PATH for php or the webserver, or if it is compiled under a different
 * name, its path can be added here. Constants should use the format "PATH_EXEC_name", where name is the binary.
 *
 */

/* Here is an example using mysqldump in a non-standard location and compiled under a different name */
//define('PATH_EXEC_mysqldump', '/opt/local/bin/mysqldump5');

?>
