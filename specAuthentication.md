# Introduction #

In order to accomodate common features for user-level systems (e.g. anonymous and OpenID logins) as well as more complex feature sets (e.g. LDAP) the Mortar authentication and user system should be redesigned around a modular, dependency-injection system for mutually-compatible authentication modules.

# Details #

I propose the following structure for the authentication system and changes to existing mechanisms.

  * All elements relating to users, groups, logging in, sessions, etc. will be moved out of the Mortar module and into a new child module (tentatively Mortar/Authentication or MA for short)
  * The User model will be largely gutted and replaced. It may still exist as a dependency container that wraps individual method objects (but see below)
  * The MemberGroup model will continue to exist and will be able to contain users from any authentication method; in addition, we may want to create more "automatic" system-level groups whose contents are hardcoded and dynamically change (for example, automatic groups containing all members of a specific authentication system.)

## Core ##

The MA module will store a central list of all existing users in the users table. For each user, this will consist of an internal identifier (probably an integer ID), a display name (what's displayed as the user's name within the application), and a numeric representation of an authentication method.

The authentication methods will be registered with the system in a similar fashion to modules, models, etc. and will be stored along with numeric identifiers in an authenticationMethods table.

Each authentication method will maintain its own distinct table(s) to store relevant information. The standard method ("MortarInternal" or something) will be identical to what is currently the only method: a table storing a unique username/password combination for each user, as well as a reference email address. For OpenAuth, this would presumably store the user's full OpenId identifier as well as the server they're authenticated against and any relevant information needed to repeat that process.

We may want to store additional details about users (profile and display data, preferences, historical details, etc.) These should probably all be stored in separate tables, apart from the core users table. Ideally, we would create two Hooks in the user system, one for additional stored data and one for additional preferences, to allow new modules to tack on additional user-oriented functionality that users from all authentication methods can benefit from.

## Configuration ##

Auth methods will be added by modules, stored in a table which provides the necessary interface classes for the MA system to wrap around. By default, the system should allow the following configuration choices:

  * What authentication method(s) are enabled on the system.
  * What group(s) each newly registered user via a specific authentication method should be assigned to

Each authentication method will also need to have its own distinct configuration. (For example, OpenAuth will have to allow configuration of the specific auth providers that are permitted.)

## Registration ##

A front-facing registration system needs to be added. By default, it should at minimum request the required information for the selected authentication method, as well as any additional required information defined by plugins, and feature at least one validation method for registration (e.g. email with unique code/link.) If possible it should convert your existing "guest" session, if any, though this may be difficult to accomplish. There should also be a default feature allowing registration only against pre-specified target IDs and/or via a specific code/link.

## Issues ##

How should anonymous/non-registered users/sessions function? Should we be able to store individual non-registered users as cookied sessions and retain certain elements of their sessions over time? If we allow for features like anonymous commenting, should we store anonymous comments as each registered to an individual "user" identified by cookie/IP address, or lump them all in against a single generic "anonymous" user?