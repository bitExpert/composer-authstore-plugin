README
======

> **Note:** The plugin is deprecated! Use the built-in functionality in Composer instead!

What is the AuthStore Plugin?
----------------

The AuthStore plugin gives you the ability to store the credentials in a separate file.
That way you do not need to enter your credentials every time you run composer.

Last year I was working on a PR (composer/composer#1862) for Composer which up to now
is not merged in master. Kudos to Manuel Lemos from phpclasses.org for pointing out
that the new Plugin API of Composer might help to apply the PR without patching Composer.

How to use it?


In your COMPOSER_HOME directory add a auth.json file which should look like this:

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

The composer.json of your root project all you need is to require the AuthStore plugin
as a dependency:

	{
		"name": "my/mywebproject",
		"require": {
			"bitexpert/composer-authstore-plugin": "*"
		}
	}

You can also install the plugin globally which might be the better alternative as globally
installed plugins are loaded before local project plugins are loaded.