# Introduction #

System Profiles are files that contain the definitions of constants which control the behavior of the system. These settings are primarily in place to make development of new modules and themes easier and, for the most part, should not be altered in production environments.


# Details #

## Changing the Profile ##

Mortar loads the file data/profiles/runtime.php when it first starts up, and that file calls the profile itself. Changing the profile is as simple as changing the name of the included file in runtime.php.

## Shipped Profiles ##

Mortar ships with three profiles, each suited to a different function.

### Production ###

This is the default profile, and the one recommended for all live sites. Error displays are all suppressed, with severe errors saved to the database. All optimization features- such javascript/css minification, output compression, caching and concise html mode- are enabled, and extra information (template tags and comments) are removed.

### Development ###

This profile is used by developers when working on modules or the core system. It displays most serious errors while skipping user errors (such as authentication errors), enables strict mode, disables the cache, output compression, and other optimization features in order to make the html, javascript and css easier to work with.

### Design ###

The primary difference between the design and development profiles is the handling of errors. The design profile saves internal errors to the database instead of outputting them to the screen, since the production environment does not display errors either. Like its development counter part, this profile disables the html cleanup as well as the javascript and css minification, while also including comments to help designers see which templates are being loaded for different sections of the site.

### Testing ###

This profile is similar to the development profile but it enables a few extra features, such as caching. It also saves more error information to the database while displaying less, making it useful for testing production environments when something goes wrong.

## Custom Profiles ##