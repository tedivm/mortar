# Introduction #

Themes are used to alter the look and feel of Mortar. They can be as small as a single html file or as large and complex as a designer wished. Mortar works by using generic templates in its system which it allows module developers and, ultimately, theme designers to overwrite. This allows designers to build themes without having to know every module installed on a system.

This file a general overview of themes in general. To build your own themes check out the [Theme Building](articleThemeBuilding.md) article.

# Details #

An active theme is a collection of templates, css and javascript files from multiple places throughout the system. These files come from three places-

  * A packaged theme in the theme directory.
  * A module installed on the system.
  * The core system itself.

Each level overrides the other- if a request is put in for the "forms.css" file it is first checked for in the theme, then in various modules, and then in the core itself (_We don't actually look in each location each time, the theme class creates an index and caches it. This was just the simpliest way to explain it_).

Javascript files have the opposite precedence. Anytime a javascript library is included in the core it will load first, followed by javascript in packages and finally the theme. This is order to ensure that the system and modules rely on getting the version of each library that they expect.


## Javascript and CSS ##

Instead of being included directly in each page the scripts (javascript and CSS) are compiled into a single file for each type and minified before being outputted. An optional priority can be added to the filename to specify the order the files are placed into the folder.

> {library}.name.{priority}.js|css

Examples-
> main.css <br>
<blockquote>forms.PageAdd.css <br>
jquery.forms.js <br>
mortar.defaults.50.js <br></blockquote>

<h2>Templates</h2>

<i>For more information see the full <a href='designTemplates.md'>Templates</a> article.</i>