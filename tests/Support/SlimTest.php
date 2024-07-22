<?php namespace Onigoetz\ImagecacheTests\Support;

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Onigoetz\ImagecacheUtils\ImagecacheTestCase;
use Onigoetz\ImagecacheUtils\Support\WebTestClient;
use org\bovigo\vfs\vfsStream;

class SlimTest extends ImagecacheTestCase
{
    /**
     * @var \Slim\App
     */
    protected $app;

    /**
     * @var WebTestClient
     */
    protected $client;

    protected $presets = [
        'path_web' => 'images',
        'path_local' => '',
        'path_cache' => 'cache',
        'presets' => [
            '40X40' => [ //exact size
                ['action' => 'scale_and_crop', 'width' => 40, 'height' => 40],
            ],
        ],
    ];

    // Run for each unit test to setup our slim app environment
    public function setup(): void
    {
        // Establish a local reference to the Slim app object
        $this->app = $this->getSlimInstance();
        $this->client = new WebTestClient($this->app);
    }

    // Instantiate a Slim application for use in our testing environment. You
    // will most likely override this for your own application.
    public function getSlimInstance()
    {
        $containerBuilder = new ContainerBuilder();
        $container = $containerBuilder->build();
        AppFactory::setContainer($container);

        $instance = AppFactory::create();

        $this->getImageFolder();
        $this->presets['path_local'] = vfsStream::url('root');

        \Onigoetz\Imagecache\Support\Slim\ImagecacheRegister::register($instance, $this->presets);

        return $instance;
    }

    public function testIsManager()
    {
        $this->assertInstanceOf('\Onigoetz\Imagecache\Manager', $this->app->getContainer()->get('imagecache'));
    }

    public function testRoute()
    {
        $route = $this->app->getRouteCollector()
            ->getNamedRoute('onigoetz.imagecache');

        $this->assertInstanceOf(\Slim\Routing\Route::class, $route);
        $this->assertEquals("/{$this->presets['path_web']}/{$this->presets['path_cache']}/{preset}/{file:.*}", $route->getPattern());
    }

    public function testRequestImage()
    {
        $image = $this->getDummyImageName();

        $file = "/{$this->presets['path_cache']}/40X40/$image";

        $this->client->get("/{$this->presets['path_web']}$file");

        $this->assertEquals(200, $this->client->response->getStatusCode());

        $tmpfile = tempnam(sys_get_temp_dir(), 'imgcache');
        file_put_contents($tmpfile, $this->client->response->getBody());

        $size = getimagesize($tmpfile);

        $this->assertEquals(40, $size[0], 'width must be 40');
        $this->assertEquals(40, $size[1], 'height must be 40');

        $this->assertFileEquals($tmpfile, vfsStream::url('root') . $file);
    }

    public function testRequestImageInvalidPreset()
    {
        $image = $this->getDummyImageName();

        $file = "/{$this->presets['path_web']}/{$this->presets['path_cache']}/50X40/$image";

        $this->client->get($file);
        $this->assertEquals(404, $this->client->response->getStatusCode());
    }

    public function testRequestImageNonExistingImage()
    {
        $file = "/{$this->presets['path_web']}/{$this->presets['path_cache']}/40X40/foo.png";

        $this->client->get($file);
        $this->assertEquals(404, $this->client->response->getStatusCode());
    }

    public function testRequestCorruptImageFile()
    {
        $image = $this->getDummyImageName();

        file_put_contents($this->presets['path_local'] . "/$image", 'oxo');

        $file = "/{$this->presets['path_web']}/{$this->presets['path_cache']}/40X40/$image";

        $this->client->get($file);
        $this->assertEquals(500, $this->client->response->getStatusCode());
    }
}
