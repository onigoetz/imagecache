<?php namespace Onigoetz\ImagecacheTests\Support;

use Onigoetz\ImagecacheUtils\Support\Laravel5TestCase;
use org\bovigo\vfs\vfsStream;

class Laravel5Test extends Laravel5TestCase
{
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

    public function setUp(): void
    {
        parent::setUp();

        $this->getImageFolder();
        $this->presets['path_local'] = vfsStream::url('root');

        $this->app['config']->set('imagecache', $this->presets);
    }

    public function testIsManager()
    {
        $this->assertInstanceOf('\Onigoetz\Imagecache\Manager', $this->app['imagecache']);
    }

    public function testRequestImage()
    {
        //$this->markTestSkipped('not ready');

        $image = $this->getDummyImageName();

        $file = "/{$this->presets['path_cache']}/40X40/$image";

        $response = $this->call('GET', "/{$this->presets['path_web']}$file");
        $this->assertEquals(200, $response->getStatusCode());

        $tmpfile = tempnam(sys_get_temp_dir(), 'imgcache');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();
        ob_start(); //if we don't do a new ob_start the test is marked as risky

        file_put_contents($tmpfile, $content);

        $size = getimagesize($tmpfile);

        $this->assertEquals(40, $size[0], 'width must be 40');
        $this->assertEquals(40, $size[1], 'height must be 40');

        $this->assertFileEquals($tmpfile, vfsStream::url('root') . $file);
    }

    public function testRequestImageInvalidPreset()
    {
        $image = $this->getDummyImageName();

        $file = "/{$this->presets['path_web']}/{$this->presets['path_cache']}/50X40/$image";

        $response = $this->call('GET', $file);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Invalid preset', $response->getContent());
    }

    public function testRequestImageNonExistingImage()
    {
        $file = "/{$this->presets['path_web']}/{$this->presets['path_cache']}/40X40/foo.png";

        $response = $this->call('GET', $file);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('File not found', $response->getContent());
    }

    public function testRequestCorruptImageFile()
    {
        $image = $this->getDummyImageName();

        file_put_contents($this->presets['path_local'] . "/$image", 'oxo');

        $file = "/{$this->presets['path_web']}/{$this->presets['path_cache']}/40X40/$image";

        $response = $this->call('GET', $file);
        $this->assertEquals(500, $response->getStatusCode());
    }
}
