# DokuWiki Shibboleth Authentication Plugin

* Homepage: [DokuWiki Shibboleth Authentication Plugin](https://github.com/ivan-novakov/dokuwiki-shibboleth-auth)
* License: [BSD 3 Clause](http://debug.cz/license/bsd-3-clause)
* Author: [Ivan Novakov](http://novakov.cz/)

## Introduction

[DokuWiki](https://www.dokuwiki.org/) is a flexible and simple wiki system written in PHP. [Shibboleth](http://shibboleth.net/) is widely used open-source implementation of SAML. DokuWiki supports different authentication plugins and it is easy to write an alternative authentication plugin to integrate your own authentication mechanism within DokuWiki.

This plugin uses a Shibboleth session to authenticate users. It just takes all required information from the environment variables injected by Shibboleth (user's attributes sent by the identity provider).

The plugin requires DokuWiki version __2013-05-10 Weatherwax__ or newer. The older versions have different authentication structure - _authentication backends_. In case you have an older version and you don't want to upgrade, you may use the [Shibboleth authentication backend](https://github.com/ivan-novakov/dokushib).

## Requirements

* PHP >= 5.x
* Shibboleth SP 2.x instance
* DokuWiki __2013-05-10 Weatherwax__ or newer

## Features

* highly configurable
* includes an action plugin to handle login actions
* different group sources
* logging and debugging

## Shibboleth configuration

You need Shibboleth SP 2.x installed and running. In Apache you have to configure Shibboleth to "know" about your DokuWiki directory:

    <Directory "/var/www/sites/dokuwiki/">
      AuthType shibboleth
      require shibboleth
    </Directory>

## Plugin installation and configuration

Clone the [repository](https://github.com/ivan-novakov/dokuwiki-shibboleth-auth) anywhere on your system. Copy the `plugin/authshibboleth` directory to `DOKUWIKI_HOME/lib/plugins`. 

In `DOKUWIKI_HOME/conf/local.conf` set the `authtype` directive:

    $conf['authtype'] = 'authshibboleth';

Alternatively, you can use the configuration manager.

Now, in most cases, the Shibboleth authentication should work out-of-the-box. But if that is not the case or you need to tune something, there is a bunch of configuration options you can set. 

The best way to do this is to copy the `conf/authshibboleth.conf.php` file from the repository to `DOKUWIKI_HOME/conf` and include it in your `DOKUWIKI_HOME/conf/local.protected.php` file (it doesn't exist by default, you have to create it yourself):

    include __DIR__ . '/authshibboleth.conf.php';
    
It's better to use `local.protected.php` instead of `local.php`, because `local.php` may be overwritten if you use the configuration manager.

The `authshibboleth.conf.php` file contains all available directives, set to their default values and commented out. If you need to change a directive, just uncomment it and change its value.

 