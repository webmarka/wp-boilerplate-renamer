# wp-boilerplate-renamer (PHP-CLI)

WPPB Renamer - The PHP Script to rename recursively files and dirs of the WordPress 
Plugin Boilerplate and replace the text in them. The main purpose of this 
script is to easen WordPress plugin developpement
by providing a quick way to rename plugins or themes.

## Features

* Command-line interface locally with php-cli;
* Command-line interface to your server via SSH and php-cli;
* Good old $_GET method;

## What about it ?

WPPB Renamer is primarily aimed to be used to customize a fresh 
sample of the WordPres Plugin Boilerplate (for instance, when you clone
or download the project right from GitHub) and do all the file renaming
and strings renaming task. 

If you are satisfied with the original WPPB, then you can already use 
the excellent http://wppb.me/ web app that furfill the need perfectly.

But when you start to customize your own fork of the boilerplate, then
wppb.me can't do it and you were back to the beginnig, starting your 
projects with a repetitive search and replace session... 

This was until today! Now you have more options :

## How to use
* Put the file renamer.php to your plugin directory with unpucked wordpress boilerplate files.
* And run it using one of the following modes :

Shell usage : `php renamer.php <options>`
* Ex. (default mode, step-by-step): php renamer.php
* Ex. (with params specified): php renamer.php -ini "Initial Name" -new "New Name" ...

HTTP usage : `/folder-name/renamer.php`
* Ex. (default mode): http://site.loc/wp-content/plugins/plugin-name/renamer.php
* Ex. (with params specified): renamer.php?initial_name=Initial+Name&new_name=New+Name& ...

## Recommended Tools

* [WordPress-Plugin-Boilerplate](https://github.com/DevinVinson/WordPress-Plugin-Boilerplate)

## Credits 
* Forked and improved by webmarka (Marc-Antoine Minville)
* Github : https://github.com/webmarka/wp-boilerplate-renamer
* Inspired from an original script by eugen (5/4/15 10:59 PM) 
* Github : https://github.com/EugenBobrowski/wp-boilerplate-renamer
* Special thanks to eugen for having starting this little project :)
* ...And to every WPPB contributor!
