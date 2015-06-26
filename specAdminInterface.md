# Introduction #

Mortar currently features a mature and well-established interface for _location model-specific_ tasks, but the broader admin interface is skeletal at best. This document aims to specify a structure for this interface that will be implemented.

# Details #

The administrative user interface should be divided into conceptual groups, each of which encompasses a variety of specific actions which work together to manage elements of the area in question.

## Modules ##

The module interface will serve as the primary means of interacting with Mortar's numerous subsidiary modules.

  * **Module List**: A sortable, paginated list of all currently installed modules, possibly separated by some form of category taxonomy, and each with a variety of useful metadata (icon, name, summary, version, date, status). It should be possible to activate and deactivate modules from this interface, as well as to access settings actions for each such module.

  * **Package Manager**: A function of the Quarry module, this should provide a searchable database of registered Mortar modules, including all the above metadata along with collective-wisdom information, update frequency, version compatibility, etc. with a simple (one-click) download -> install process for each.

  * **Module Settings**: Each module should have a pre-defined "settings" page; this should combine a set of universal module settings with any custom settings unique to that module.

## Users ##

The user/group interface will be used for all user/group related tasks.

  * **User List**: A sortable, paginated list of all current users, separatable by group. By default this would list username, user status, current group(s), etc. and provide options to add, edit, or delete users.

  * **Group List**: Similar to the user list, but it should show a count of users and possibly a "summary" of current permissions/settings.

  * **Individual User/Group Edit**: For each user/group, this should include both form elements required to alter the specific qualities of the user (profile info, group memberships, status, name, etc.) and certain info pages: for example, there should be a "Permissions Tree" page that you can click to see a full accounting of how all permissions are assigned for this user/group at all locations (or all locations descending from a specified location.)

  * **User/Group Add**: Like our other Add actions, this will serve as the empty template upon which the Edit form is based. _Unlike_ our other Add actions, this may need to be forward-facing in order to serve as a user-registration form as well, so it should be designed with that possibility in mind.

  * **User/Group Delete**: I'm not sure there's much needed here except a checkbox and a confirm button.

## Sites ##

The Site interface will be used to manage individual sites (that is, top-level or sub-domains)

  * **Site List**: Similar to other lists above, a sortable list of sites by name

  * **Site Add/Edit**: This probably needs to include the site name and subtitle, base URL, site-root permissions...

What else is needed here?

## Themes ##

This will be entirely a product of the Mural module; it will include a variety of theme-related settings. Our current thoughts for which features should be included in Mural are listed in the roadmapMural wiki page.

## Settings ##

The settings section needs to include a variety of system-wide, non module-specific settings for the current Mortar install as a whole. It should feature a variety of individual, concept-divided "panes" or action pages which can be expanded or added onto by other modules.

  * **Profiles**: This would allow the user to add, modify, and switch profiles.

  * **Urls**: This would allow the user to modify the nice-URL structure, and possibly adjust canonical URL choices (e.g. with/without www)

  * **Locality**: Timezone, default date/time formats, language settings

  * **Comments/Posting**: Global settings regarding unregistered commenting/posting, moderation, spam protection, etc. (some of these will have to be overrideable on an individual-location level.)

  * **Cache**: There are probably some settings regarding our cacheing behavior that we want to expose to the user for changing?

What else should go here?

## Other Tools ##

This is a catch-all category for specific, major actions that do not fit in another category. Like the Settings category, this should be defined as individual panes which can be expanded or added onto by other modules.

  * **Import Data**: This should allow the importing of data from major competing projects (Wordpress, phpBB, vBulletin, etc.)