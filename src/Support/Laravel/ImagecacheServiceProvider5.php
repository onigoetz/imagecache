<?php namespace Onigoetz\Imagecache\Support\Laravel;

class ImagecacheServiceProvider5 extends ImagecacheServiceProvider {

    /**
     * Add the namespace to config
     */
    public function registerConfiguration()
    {
        $this->publishes(
            [
                __DIR__ . '/../../config/imagecache.php' => config_path('imagecache.php'),
            ]
        );
    }

    public function getConfiguration()
    {
        return $this->app['config']->get('imagecache');
    }
}
