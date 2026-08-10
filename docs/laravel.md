# Imagecache with Laravel

Supports Laravel 12 and 13.

### Dependency

Add a dependency with this command : `composer require onigoetz/imagecache`

### Service Provider

`Onigoetz\Imagecache\Support\Laravel\ImagecacheServiceProvider` is registered
automatically through Laravel's package discovery, there is nothing to do.

If you opted out of discovery for this package, add the provider to
`bootstrap/providers.php` yourself:

```php
return [
    App\Providers\AppServiceProvider::class,
    Onigoetz\Imagecache\Support\Laravel\ImagecacheServiceProvider::class,
];
```

### Publish configuration

Publish the configuration to be able to add your own presets

`./artisan vendor:publish`

You're now ready to use the package
