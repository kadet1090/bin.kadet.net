Kadet's bin
===========

Simple silex based pastebin for pasting pastes, with [KeyLighter] inside.
![Screenshot](http://i.imgur.com/7QekSNW.png)

Live demo (actually used!): http://bin.kadet.net/

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

## File Uploaders configuration

 - **Address**: Base address of installation, for example: `http://bin.kadet.net`
 - **Request type**: `POST`
 - **Content field**: `paste`
 - **Additional (optional) fields**:
    - `key`: Key for edition authorization
    - `author`: Your name or nickname
    - `language`: Language name, as described in: http://keylighter.kadet.net/docs/languages
    - `title`: Paste title
    - `description`: Paste description
    - `lines`: Line mappings, `!2:10` - highlights lines from 2 to 10, `1:10` - makes line 1 to appear as 10

![ShareX config](http://i.imgur.com/It9I8fa.png)

[KeyLighter]: http://github.com/kadet1090/keylighter