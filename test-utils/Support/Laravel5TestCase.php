<?php namespace Onigoetz\ImagecacheUtils\Support;

use Mockery as m;
use Onigoetz\Imagecache\Manager;
use Onigoetz\ImagecacheUtils\ImagecacheTestTrait;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class Laravel5TestCase extends \Orchestra\Testbench\TestCase
{
    use ImagecacheTestTrait;

    /**
     * {@inheritdoc}
     */
    protected function getEnvironmentSetUp($app)
    {
        // reset base path to point to our package's src directory
        $app['path.base'] = realpath(__DIR__ . '/..');
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackageProviders($app)
    {
        return [
            \Onigoetz\Imagecache\Support\Laravel\ImagecacheServiceProvider::class
        ];
    }
}
