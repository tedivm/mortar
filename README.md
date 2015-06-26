# mortar
Automatically exported from code.google.com/p/mortar


## Retired Project

This project is no longer being developed, and exists here primarily for historical purposes. Some of it's code has spun off into stand alone projects that are currently being developed-

  * [Stash](https://github.com/tedivm/Stash) is an advanced caching library.
  * [JShrink](https://github.com/tedivm/JShrink) is a javascript minifier.
  * [Fetch](https://github.com/tedivm/Fetch) is an email reading (imap, pop) library.

To stay up to date on my projects, please visit my [http://blog.tedivm.com blog].

# Historic Documentation

## Overview

Mortar is similar to a framework in the sense that it offers a number of tools to aid development, but Mortar takes things a step further by implementing a working base to build off of. By providing a working base, with all the core functionality required by modern web applications, Mortar simplifies the development, design and use of websites. At the same time Mortar does not try to accomplish everything. While there is certainly a robust core the system is, by itself, almost featureless from the end user perspective and very lightweight. By installing modules the system can be extended to fit almost any need. 

## Modules

Mortar holds sites together but it needs something to hold, and thats where modules come in. Modules tend to fit into three categories-

 * Libraries provide support and classes beyond those that ship with Mortar. 
 * Plugins alter the functionality of other modules, plugins and the core system.
 * Applications provide the bulk of a user's experience with the system.

These categories not code or policy based, all modules are managed and put together in the same way. The majority of modules have aspects that fit into more than one category, as an application may want to provide its internal libraries for use in other applications or it may need to hook into the system for various reasons.

## Goals

 * Web applications should not be islands on a website but rather should integrate seamlessly.
 * Developers should be able to create programs that work together naturally, without awkward bridges.
 * Designers should be able to make themes as simple or complex as they desire without having to rebuild them for different programs on their site.
 * Users should have a positive and consistent experience throughout all aspects of a website.

To accomplish these goals we built an extensible system that handles most of the basic functionality shared by all web applications. The core itself does not offer much in the way of features but rather provides all the tools needed to add those features through the use of modules and plugins.

## Features

 * Fast. No seriously, look at the features below and you'll see why.
 * Modular caching system integrated directly into the core allows for extreme performance even when not all of the data is returned.
 * Full client side caching, CDN and proxy support.
 * Automatic minifies and combines JS and CSS with a unique name for each version and a far-future expiration.
 * Full client and server side validation with the use of the Form class.
 * Modular io handlers let all actions run over http, rest, cli or cron jobs.
 * Support for multiple sites.
 * Tree based location structure for models with a flexible permissions system.
 * Full theming support.

## Requirements

 * PHP 5.2.8 with SQLite support
 * MySQL 5
 * Unix style operating system (Tested on OSX, Debian and CentOS)
 * Mod_rewrite suggested for prettier URLS (although URLs aren't awful without)

## Roadmap

Mortar is not quite at version 1.0 but we're working towards it. Take a look at our [roadmapCore Project Roadmap] to whats coming up or how you can help.

## Help Wanted

We are currently looking for people interested in working on Mortar or its related projects. If you are interested in building themes, creating modules, testing and submitting ideas from the user perspective, enhancing the user interface, or anything else related to Mortar you should join our development list.

You can also join us in ##mortar on freenode.

