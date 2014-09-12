# Imagecache with Raw PHP

You can also use imagecahce without any framework, just pure PHP, here's how.

## URL Rewriting

First, you need a rewrite rule, we'll do the apache one in this example.

put the following inside a `.htaccess` file

```
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^/images/cache/(.*)/(.*)$ images.php?preset=$1&file=$2 [L,QSA]
```

This will allow us to have a URL like `/images/cache/200x200/koala.jpg`
where the preset is `200x200` and the file is `koala.jpg`

so for your example the internal url would be : `images.php?preset=200x200&file=koala.jpg`

## The code

Create an array that follows the configuration from the model in [The default configuration](https://github.com/onigoetz/imagecache/blob/master/src/config/imagecache.php)

Then we create the file `images.php`

```php
<?php

include 'vendor/autoload.php';

$config = //your configuration here …

\Onigoetz\Imagecache\Support\Raw\RawImagecacheService::run($config);

```


