# Introduction #

The current menu system in Mortar is hacky, awkward, and unsustainable. It needs to get fixed up.

## Proposed System ##

### Storage ###

All menus and system-generated sidebars, for both admin and user-side pages, are managed by the MenuSystem class, of which a single copy is instantiated for each Page class.

MenuSystem stores instances of the Menu class in an associative array. Menus each in turn contain an array of menu items, which are either strings or other menus.

```

['main']
 => ['Business']
   => 'Go To Work'
   => 'Sleep'
 => ['Pleasure']
    => 'Throw Party'
    => 'Wear Hats'
    => 'Eat Cake'
    => ['Buy Decorations']
      => 'Balloons'
      => 'Streamers'
      => 'Rocks'
['secondary']
 => ['Colors']
    => 'Green'
    => 'Grey'
    => 'Black'
    => 'Ochre'

```

When a MenuSystem is instantiated, it creates menus based on three pieces of information:

  * Any menu items it is passed in a constructor array.
  * All menuItem plugins which add universal menu items
  * All menuItem plugins which add menu items relevant to the type of Model currently viewed.

In addition, extra menu items can be added to any menu through a method of the MenuSystem.

### Display ###

Menus are rendered via a ViewMenuDisplay, which renders them using two templates: a container template for the whole menu and an item template for each individual menu item. ViewMenuDisplay utilizes a default template name for rendering a menu unless the "template" field of the Menu object is set, in which case it utilizes that template instead.

### Templating ###

Menus are passed to theme templates via a MenuTagBox, which takes as an input the current MenuSystem.

Menus can be inserted into templates using several distinct syntaxes:

{{ menu.name }} -- inserts the menu of the listed name

{{ menu[_n_] }} -- inserts the _n\_th menu_

{{ menu.next }} -- inserts the next highest numbered menu that has yet to be inserted in the current page

{{ menu.remaining }} -- inserts all remaining menus, one after another