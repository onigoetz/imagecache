<?php
/**
 * Created by IntelliJ IDEA.
 * User: sgoetz
 * Date: 12.09.14
 * Time: 15:40
 */

namespace Onigoetz\Imagecache\Support;

use ImagecacheTestCase;
use org\bovigo\vfs\vfsStream;
use There4\Slim\Test\WebTestClient;

class SlimTest extends ImagecacheTestCase {

    /**
     * @var \Slim\Slim
     */
    protected $app;

    /**
     * @var WebTestClient
     */
    protected $client;

    protected $presets = array(
        'path_images' => 'images',
        'path_images_root' => '',
        'path_cache' => 'cache',
        'presets' => array(
            '40X40' => array( //exact size
                array('action' => 'scale_and_crop', 'width' => 40, 'height' => 40)
            ),
        )
    );

    // Run for each unit test to setup our slim app environment
    public function setup()
    {
        // Establish a local reference to the Slim app object
        $this->app = $this->getSlimInstance();
        $this->client = new WebTestClient($this->app);
    }

    // Instantiate a Slim application for use in our testing environment. You
    // will most likely override this for your own application.
    public function getSlimInstance() {
        $instance = new \Slim\Slim(array(
            'version' => '0.0.0',
            'debug'   => false,
            'mode'    => 'testing'
        ));

        $this->getImageFolder();
        $this->presets['path_images_root'] = vfsStream::url('root');

        \Onigoetz\Imagecache\Support\Slim\ImagecacheRegister::register($instance, $this->presets);

        return $instance;
    }

    public function testIsManager()
    {
        $this->assertInstanceOf('\Onigoetz\Imagecache\Manager', $this->app->imagecache);
    }

    public function testRoute()
    {
        $route = $this->app->router->getNamedRoute('onigoetz.imagecache');

        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertEquals("/{$this->presets['path_images']}/{$this->presets['path_cache']}/:preset/:file", $route->getPattern());
    }

    public function testRequestImage()
    {
        $image = $this->getDummyImageName();

        $file = "/{$this->presets['path_images']}/{$this->presets['path_cache']}/40X40/$image";

        $this->client->get($file);
        $this->assertEquals(200, $this->client->response->status());

        $tmpfile = tempnam(sys_get_temp_dir(), 'imgcache');
        file_put_contents($tmpfile, $this->client->response->body());

        $size = getimagesize($tmpfile);

        $this->assertEquals(40, $size[0], 'width must be 40');
        $this->assertEquals(40, $size[1], 'height must be 40');

        $this->assertFileEquals($tmpfile, vfsStream::url('root') . $file);
    }

    public function testRequestImageInvalidPreset()
    {
        $image = $this->getDummyImageName();

        $file = "/{$this->presets['path_images']}/{$this->presets['path_cache']}/50X40/$image";

        $this->client->get($file);
        $this->assertEquals(404, $this->client->response->status());
    }

    public function testRequestImageNonExistingImage()
    {
        $file = "/{$this->presets['path_images']}/{$this->presets['path_cache']}/40X40/foo.png";

        $this->client->get($file);
        $this->assertEquals(404, $this->client->response->status());
    }
}
