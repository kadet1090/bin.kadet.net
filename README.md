Kadet's bin
===========

Simple silex based pastebin for pasting pastes, with [KeyLighter] inside.
![Screenshot](http://i.imgur.com/7QekSNW.png)

## Installation

```shell
composer install
yarn
gulp 
php bin/console migrate
```

Use `web` folder as servers document root, for example:

```shell
$ php -S pastebin.dev:8080 -t web
```

[KeyLighter]: http://github.com/kadet1090/keylighter