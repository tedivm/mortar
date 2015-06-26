# Introduction #

Mortar makes use of the [Twig](http://www.twig-project.org/) Twig template language for PHP. As a strong, flexible, and fast template language built in an object-oriented style, it is a perfect fit for Mortar's core goals and gives our project an expressive power for its templates that few other content systems currently offer.

# Integration #

Currently Twig is accessed in Mortar through a series of three loaders attached to _View classes_, each of which uses a different procedure for selecting templates.

The simplest, _ViewStringTemplate_, is passed a raw string and uses that as its template. It does not load any exterior templates.

The second View, _ViewThemeTemplate_, loads templates intended for page display -- non-model specific content. It takes a template filename and checks along a specific path to locate the template that will be used: first the root of the current theme, then the roots of any ancestor themes, then finally the system/templates directory.

The third, _ViewModelTemplate_, is intended to load templates which are model-specific. It checks a longer list of locations: first the models/ModelName directory in the current theme, the module that hosts the current model, and the system/templates directory; then the models/base directory of each.

Error handling has been managed by defining the "Twig\_Error" class as an extension of CoreError.

The Autoloader now has a function for loading external classes and the Twig\_Autoloader class has been integrated into that.

# Extensions #

Twig extensions are enhancements to the language itself. These are still being defined.

## Date/Time ##

Various date and time functions should be exposed to templates. This extension is not really Mortar specific and should be shared back with Twig. Formatting is already handled by Twig.

### Variables ###

  * Current Time
  * Current Timezone

### Expressions ###

I'm not sure how feasible this is, but I'd like to be able to use expressions (addition, subtraction, comparison, etc) on date and time objects. I'm thinking the easiest way to do this would be to store them as timestamps until they're displayed.

## Urls ##

This extension should provide designers with a simple way to manage Urls. Url attributes should be accessible as variable attributes for both accessing and changing.

### Variables ###

  * Current URL
  * Site URL

### Tags ###

  * New URL

## Multilingual Support ##

  * Ability to switch between languages for displayed text.

# Content Objects #

Because Twig allows public methods and properties to be accessed from an object that is passed to it as template content, we can create helper objects that use the get magic function to return specific pieces of content to the template _only_ when it is requested. This conveniently gives us the secondary effect of delineating different areas of tags through pseudo-namespaces -- i.e. you get tags like {{ nav.prev }}, {{ model.title }}, etc.

My current thinking is to use these objects to clearly delineate which content tags are available at the page level vs. the model level.

### Theme ###

Tags: path, images[.md](.md), meta[.md](.md)

Available to: Page, Model

### Environment ###

Tags: site path, site name, current username, current group

Available to: Page, Model

### Model ###

Tags: all content and qualities of the model, wrapped through an HTML converter

Available to: Model

### Navigation ###

Tags: previous, next, children-list, peers-list, paging-list

Available to: Page

### Page Structure ###

Tags: insert menu/sidebar, insert secondary content block, etc.

Available to: Page

# For the Future #

Do we want to make a series of hooks that allow us to smoothly insert new content objects into the rotation for page and model templates? Do we want to integrate these objects directly into the loaders/constructors so that they're always available in the relevant circumstances or manually load them only when expected in the Mortar code? Any choices we make here are going to have a performance/flexibility tradeoff.

We also may want to think carefully about the dividing line between extensions and content objects. My current thought is that almost anything that relies on Mortar-specific content is better realized as an object while anything that uses exclusively "universal" info is probably better off as an extension, but I need a second (and third, and fourth...) pair of eyes on this.