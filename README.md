Ochenta: HTTP request/response implementation
=============================================

HTTP abstraction layer in php 7 with [psr-7](http://www.php-fig.org/psr/psr-7/) basic implementation.

This is just a PoC. DO NOT USE IT IN PRODUCTION.

Usage
-----

```php
$req = new Ochenta\ServerRequest;
```

It could also be created with it's defaults values:

```php
$req = new Ochenta\ServerRequest($_SERVER, $_GET, $_POST, $_FILES, null);
```

That's a request. There is `Ochenta\Request` but is not recomended to be used alone as it doesn't normalize any value.

There is `Ochenta\Response` but is no worth using it.

Badges
------

[![Latest Stable Version](https://poser.pugx.org/guide42/ochenta/v/stable.svg)](https://packagist.org/packages/guide42/ochenta)
[![Build Status](https://travis-ci.org/guide42/ochenta.svg?branch=master)](https://travis-ci.org/guide42/ochenta)
[![Code Coverage](https://scrutinizer-ci.com/g/guide42/ochenta/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/guide42/ochenta/?branch=master)
