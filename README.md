# UNB to Flarum Converter

This is a script that will import contents of the database of an [Unclasified Newsboard](http://newsboard.unclassified.de/) forum into a [Flarum](https://flarum.org/) database.

This has been tested with Flarum `0.1.0-beta.12` and UNB `20150713-dev`.

## Features

  * Forums -> Tags
  * Threads -> Discussions
  * Posts -> Posts
  * Users -> Users
  * Groups -> Groups

## Requirements

You need to start with a completely empty Flarum database. You should delete the default "General" tag before importing.

Your UNB and your Flarum database need to be in the same MySQL/Maria server and you need a database user that can access both databases. This is needed because I run some queries that execute things in both databases at once.

You need to install and activate the following extensions in Flarum:

  * [Old Passwords](https://discuss.flarum.org/d/8631-old-passwords) to let users login with their old UNB passwords
  * [User Bio](https://discuss.flarum.org/d/17775-friendsofflarum-user-bio) to let users have a biography field again 
  * [Masquerade](https://discuss.flarum.org/d/5791-masquerade-by-friendsofflarum-the-user-profile-builder) to import additional profile data

## Installation

Check out this repository, then install the dependencies:

    composer install

Copy the provided `config.php` and edit it for your setup.

    cp config.php myconfig.php
    vim myconfig.php

Then run the converter

    php unb2flarum.php -c myconfig

## Caveats

The whole import is executed with a transaction. If something goes wrong, nothing will be committed to your Flarum database. You might want to do a dump of your empty setup beforehand anyway, in case you want to tweak and rerun the import again.

This currently does not support Polls. There is a poll extension for Flarum, but the feature was so rarely used in my UNB that I simply didn't bother with it.

Categories aka Forums aka Tags can be nested arbitrarily deep in UNB. Flarum treats them more like tags and also allows nesting for two levels only. I assume that you may want to restructure your categorisation for Flarum anyway, so all categories are importet as first level tags. Rearrange them as you see fit.
