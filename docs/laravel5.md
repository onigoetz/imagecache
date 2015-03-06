# Imagecache with Laravel 5

If you've already installed a Laravel Package this will be nothing new to you.

### Dependency

Add a dependency with this command : `composer require onigoetz/imagecache:dev-master`

### Service Provider

Add `Onigoetz\Imagecache\Support\Laravel\ImagecacheServiceProvider5` in `app/config/app.php` in the `'providers'` array

### Publish configuration

Publish the configuration to be able to add your own presets

`./artisan vendor:publish`


You're now ready to use the package
