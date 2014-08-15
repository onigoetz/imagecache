<?php

use Mockery as m;
use Onigoetz\Imagecache\Image;
use Onigoetz\Imagecache\Manager;
use Onigoetz\Imagecache\MethodCaller;
use org\bovigo\vfs\vfsStream;

class MethodCallerTest extends ImagecacheTestCase
{
    function getImage()
    {
        return new Image(vfsStream::url('root/images') . '/' . $this->getDummyImageName(), $this->getMockedToolkit());
    }

    /**
     * @covers Onigoetz\Imagecache\MethodCaller::call
     */
    function testCallReorderArgs()
    {
        $config = ['height' => 100, 'width' => 200];

        $image = m::mock($this->getImage());
        $image->shouldReceive('scale_and_crop')->with($config['width'], $config['height'])->andReturn(true);

        (new MethodCaller)->call($image, 'scale_and_crop', $config);
    }

    /**
     * @expectedException \LogicException
     * @covers Onigoetz\Imagecache\MethodCaller::call
     */
    function testCallMethodDoesntExist()
    {
        (new MethodCaller)->call($this->getImage(), 'foo', []);
    }

    /**
     * @covers Onigoetz\Imagecache\MethodCaller::call
     */
    function testCallFindsDefaultArgs()
    {
        $config = ['degrees' => 90];

        $image = m::mock($this->getImage());
        $image->shouldReceive('rotate')->with($config['degrees'], null, false)->andReturn(true);

        (new MethodCaller)->call($image, 'rotate', $config);
    }
}
