# Imagecache with Laravel 4

If you've already installed a Laravel Package this will be nothing new to you.

### Dependency

Add a dependency with this command : `composer require onigoetz/imagecache:1.1.*`

### Service Provider

Add `Onigoetz\Imagecache\Support\Laravel\ImagecacheServiceProvider` in `app/config/app.php` in the `'providers'` array

### Publish configuration

Publish the configuration to be able to add your own presets

`./artisan config:publish onigoetz/imagecache`


You're now ready to use the package
