README
======

[![Build Status](https://travis-ci.org/bitExpert/composer-authstore-plugin.svg?branch=master)](https://travis-ci.org/bitExpert/composer-authstore-plugin)

What is the AuthStore Plugin?
----------------

The AuthStore plugin gives you the ability to store the credentials in a separate file.
That way you do not need to enter your credentials every time you run composer.

Last year I was working on a [PR](https://github.com/composer/composer/issues/1862) for Composer which up to now
is not merged in master. Kudos to Manuel Lemos from [phpclasses.org](http://phpclasses.org) for pointing out
that the new Plugin API of Composer might help to apply the PR without patching Composer. As a result this plugin
came alive.

How to use it?


Just add an `auth.json` file in your project root (aside to your main `composer.json`), which should look like this:

```json
{
    "config": {
        "basic-auth": {
            "satis.loc": {
                "username": "my_username",
                "password": "my_password"
            }
        }
    }
}
```

Alternatively, you can store your `auth.json` in `COMPOSER_HOME`, so that authentication settings is available
for all your projects.

> **Note:** Local `auth.json` always has precedence if a host is defined both locally and globally.


The composer.json of your root project all you need is to require the AuthStore plugin
as a dependency:

```json
{
    "name": "my/mywebproject",
    "require": {
        "bitexpert/composer-authstore-plugin": "*"
    }
}
```

You can also install the plugin globally which might be the better alternative as globally
installed plugins are loaded before local project plugins are loaded.
