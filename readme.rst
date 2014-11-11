..  -*- mode: rst -*-
..  -*- coding: utf-8 -*-


===========================================================================
PHP Memcached Client (simulator)
===========================================================================



As there has no php memcached extension for windows now, it's difficult to
build develop envionment, so this class will be helpful.

Inspried by: http://github.com/joonas-fi/xslib-memcached


Usage:

Just as php_memcached extension, new Memcached object and etc.

::

    $m = new Memcached();
    $m->addServer('localhost', 11211);

    $m->set('foo', 'bar');
    $m->get('foo');


Supported method:

-   addServer
-   addServers
-   delete
-   get
-   getOption
-   getResultCode
-   getResultMessage
-   getServerList
-   increment
-   set
-   setOption
-   setOptions


Need disable memcached extension of PHP to run PHPUnit testcase.


License: MIT
