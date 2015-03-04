<?php namespace Onigoetz\Imagecache\Support;

use Laravel5TestCase;
use org\bovigo\vfs\vfsStream;

class Laravel5Test extends Laravel5TestCase {

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

    public function setUp()
    {
        parent::setUp();

        $this->getImageFolder();
        $this->presets['path_images_root'] = vfsStream::url('root');

        $this->app['config']->set('imagecache', $this->presets);
    }

    public function testIsManager()
    {
        $this->assertInstanceOf('\Onigoetz\Imagecache\Manager', $this->app['imagecache']);
    }

    public function testRoute()
    {
        $this->markTestSkipped('not ready');

        $route = $this->app->router->getNamedRoute('onigoetz.imagecache');

        $this->assertInstanceOf('\Slim\Route', $route);
        $this->assertEquals("/{$this->presets['path_images']}/{$this->presets['path_cache']}/:preset/:file", $route->getPattern());
    }

    public function testRequestImage()
    {
        $this->markTestSkipped('not ready');

        $image = $this->getDummyImageName();

        $file = "/{$this->presets['path_images']}/{$this->presets['path_cache']}/40X40/$image";

        $response = $this->call('GET', $file);
        $this->assertEquals(200, $response->getStatusCode());

        $tmpfile = tempnam(sys_get_temp_dir(), 'imgcache');

        ob_start();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        file_put_contents($tmpfile, $content);

        $size = getimagesize($tmpfile);

        $this->assertEquals(40, $size[0], 'width must be 40');
        $this->assertEquals(40, $size[1], 'height must be 40');

        $this->assertFileEquals($tmpfile, vfsStream::url('root') . $file);
    }

    public function testRequestImageInvalidPreset()
    {
        $image = $this->getDummyImageName();

        $file = "/{$this->presets['path_images']}/{$this->presets['path_cache']}/50X40/$image";

        $response = $this->call('GET', $file);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Invalid preset', $response->getContent());
    }

    public function testRequestImageNonExistingImage()
    {
        $file = "/{$this->presets['path_images']}/{$this->presets['path_cache']}/40X40/foo.png";

        $response = $this->call('GET', $file);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('File not found', $response->getContent());
    }

    public function testRequestCorruptImageFile()
    {
        $image = $this->getDummyImageName();

        file_put_contents($this->presets['path_images_root'] . "/" . $this->presets['path_images'] . "/$image", "oxo");

        $file = "/{$this->presets['path_images']}/{$this->presets['path_cache']}/40X40/$image";

        $response = $this->call('GET', $file);
        $this->assertEquals(500, $response->getStatusCode());
    }
}
