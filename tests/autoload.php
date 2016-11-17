<?php

include dirname(__DIR__) . '/vendor/autoload.php';

use Mockery as m;
use Onigoetz\Imagecache\Manager;
use org\bovigo\vfs\vfsStream;

trait ImagecacheTestTrait
{
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    protected $vfsRoot;

    public function getDummyImageName()
    {
        return '500px-Smiley.png';
    }

    public function getImageFolder()
    {
        $this->vfsRoot = vfsStream::setup('root');
        vfsStream::copyFromFileSystem(__DIR__ . '/Fixtures/source');

        return vfsStream::url('root');
    }
}

abstract class ImagecacheTestCase extends \PHPUnit_Framework_TestCase
{
    use ImagecacheTestTrait;

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        m::close();
    }

    public function getManager($options = [])
    {
        //Add default option
        $options += ['path_local' => $this->getImageFolder()];

        return new Manager($options);
    }

    public function getMockedManager($options = [])
    {
        //Add default option
        $options += ['path_local' => $this->getImageFolder()];

        return m::mock('Onigoetz\Imagecache\Manager', [$options])
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
    }
}

class Laravel5TestCase extends \Orchestra\Testbench\TestCase
{
    use ImagecacheTestTrait;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        if (!$this->app) {
            $this->refreshApplication();
        }

        $artisan = $this->app->make('Illuminate\Contracts\Console\Kernel');
        $artisan->call('vendor:publish');

        //refresh configuration values
        $this->refreshApplication();
    }

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
        return ['\Onigoetz\Imagecache\Support\Laravel\ImagecacheServiceProvider'];
    }
}
