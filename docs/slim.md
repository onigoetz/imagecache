# Imagecache with Slim Framework

## Composer

Add a dependency with this command : `composer require onigoetz/imagecache:dev-master`

## Creating a configuration

Create an array that follows the configuration from the model in [The default configuration](https://github.com/onigoetz/imagecache/blob/master/src/config/imagecache.php)

After you created an application and prepared your configuration, add this one-liner in your configuration

```
Onigoetz\Imagecache\Support\Slim\ImagecacheRegister::register($slim_application, $preset_configuration);
```

you're now ready to use imagecache in your project
