<?php namespace Onigoetz\ImagecacheTests;

use Mockery as m;
use Onigoetz\Imagecache\Image;
use Onigoetz\Imagecache\MethodCaller;
use Onigoetz\ImagecacheUtils\ImagecacheTestCase;
use org\bovigo\vfs\vfsStream;

class MethodCallerTest extends ImagecacheTestCase
{
    public function getImage()
    {
        return new Image(vfsStream::url('root') . '/' . $this->getDummyImageName());
    }

    /**
     * @covers Onigoetz\Imagecache\MethodCaller::call
     */
    public function testCallReorderArgs()
    {
        $config = ['height' => 100, 'width' => 200];

        $image = m::mock($this->getImage());
        $image->expects()->scale_and_crop($config['width'], $config['height'])->andReturn(true);

        (new MethodCaller)->call($image, 'scale_and_crop', $config);
    }

    /**
     * @covers Onigoetz\Imagecache\MethodCaller::call
     */
    public function testCallMethodDoesntExist()
    {
        $this->expectException(\LogicException::class);
        (new MethodCaller)->call($this->getImage(), 'foo', []);
    }

    /**
     * @covers Onigoetz\Imagecache\MethodCaller::call
     */
    public function testCallFindsDefaultArgs()
    {
        $config = ['degrees' => 90];

        $image = m::mock($this->getImage());
        $image->expects()->rotate($config['degrees'], null, false)->andReturn(true);

        (new MethodCaller)->call($image, 'rotate', $config);
    }
}
