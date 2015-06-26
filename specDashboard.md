# Introduction #

In order to bring Mortar's administrative interface to a fully-functioning state, one element that is desperately needed is a dashboard: a landing page which can present a variety of information at once and be customizable to each user's needs.

# Basics #

The fundamental structure of the Mortar Dashboard will be built around our action system. Mortar actions already function by returning content which is injected into a template; our dashboard will rely on actions returning content from a viewDashboard() function, which will allow us to easily integrate actions which already provide content that should be viewable on the Dashboard into the system while still allowing the content to be custom-designed for the needs of dashboard display.

## Appearance ##

The Mortar Dashboard will appear similar to the standard admin interface, but instead of a single large box in which action content is displayed, it will feature an invisible grid which includes multiple dashboard widgets (which can be sized to cover one or more grid boxes).

## Customization ##

The dashboard will be configurable for each user. (We may also want to consider the possibility of letting each user store multiple dashboards.) This will involve allowing each user to select which widgets appear on their dashboard, set options for each widget, and arrange them as they see fit.

Adding widgets should be possible through at least two mechanisms: selecting available mechanisms from a list, or clicking an "Add this to my Dashboard" link while viewing an action. All widgets should be able to be added multiple times since they may provide utility that is beneficial to have multiple times (e.g. "Recent Changes" for several different locations.)

Rearranging actions should be done simply, through dragging them by a title bar directly on the user interface (functionality powered by the jQuery UI "Draggable" method.) It may be desirable to auto-save these changes via AJAX without requiring a direct "save my configuration"

The data for each dashboard should be saved to a table, which will need to be constructed for this purpose.

## Widgets ##

  * **Recent Activity** -- This returns a date-sorted list of changes to models, which can be filtered by model type(s) and types of actions. A default site-wide "recent activity" would feature addition, editing, and deletion of models of all types, but filtered versions of this list could be used to provide many specific pieces of functionality (e.g. recent comments, new registrations, etc.)
  * **Analytics** -- To display a variety of information from the (yet to be developed) Analytics module.
  * **Bookmarks** -- A list of places inside the system that someone uses frequently and can get to with a single click.
  * **Browser** -- A Mortar "filesystem" browser that displays a navigable tree of the location structure of the current site.
  * **Settings** -- A subset of specific settings which the user wants to have available to quickly change at a moment's notice.
  * **Out-of-date modules** -- A list of installed modules which have received more recent updates at the Quarry.
  * **Messaging** -- New individual messages received by the user.