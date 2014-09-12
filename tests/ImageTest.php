<?php

use Mockery as m;
use Onigoetz\Imagecache\Image;
use org\bovigo\vfs\vfsStream;

class ImageTest extends ImagecacheTestCase
{
    function getImage()
    {
        $this->getImageFolder();
        $original_file = vfsStream::url('root/images') . '/' . $this->getDummyImageName();

        return new Image($original_file, $this->getMockedToolkit());
    }

    function getMockedImage()
    {
        return m::mock($this->getImage());
    }

    /**
     * @expectedException \Onigoetz\Imagecache\Exceptions\NotFoundException
     */
    function testFileNotFound()
    {
        $this->assertFalse(new Image('/this/file/doesnt_exist', $this->getMockedToolkit()));
    }

    /**
     * @expectedException \LogicException
     */
    function testScale_and_cropNeedsWidth()
    {
        $image = $this->getImage();

        $image->scale_and_crop(null, 300);
    }

    /**
     * @expectedException \LogicException
     */
    function testScale_and_cropNeedsHeight()
    {
        $image = $this->getImage();

        $image->scale_and_crop(300, null);
    }

    function testScale_and_cropFails()
    {
        $image = $this->getMockedImage();

        $image->getToolkit()->shouldReceive('resize')->andReturn(false);

        $this->assertFalse($image->scale_and_crop(300, 300));
    }

    function testRotateRandom()
    {
        $variation = 20;

        $matcher = function($val) use ($variation) {
            return ($val >= ($variation*-1) && $val <= $variation);
        };

        $image = $this->getMockedImage();
        $image->getToolkit()
            ->shouldReceive('rotate')
            ->with(m::type('Onigoetz\Imagecache\Image'), m::on($matcher), m::any())
            ->andReturn(true);
        $image->rotate($variation, null, true);
    }

    /**
     * @expectedException \LogicException
     */
    function testCropNeedsWidth()
    {
        $image = $this->getImage();

        $image->crop(0, 0, null, 0);
    }

    /**
     * @expectedException \LogicException
     */
    function testCropNeedsHeight()
    {
        $image = $this->getImage();

        $image->crop(0, 0, 0, null);
    }

    /**
     * @expectedException \LogicException
     */
    function testCropNeedsXOffset()
    {
        $image = $this->getImage();

        $image->crop(null, 0, 300, 300);
    }

    /**
     * @expectedException \LogicException
     */
    function testCropNeedsYOffset()
    {
        $image = $this->getImage();

        $image->crop(0, null, 300, 300);
    }

    function testSave()
    {
        $image = $this->getImage();
        $final_file = vfsStream::url('root/images') . '/test-save.png';

        $this->assertFalse(file_exists($final_file));
        $image->save($final_file);
        $this->assertTrue(file_exists($final_file));
    }

    function testSaveInPlace()
    {
        $image = $this->getImage();

        $original_size = $image->getFileSize();

        $image->scale_and_crop(300, 300);
        $image->save();

        $this->assertFalse($image->getFileSize() == $original_size);
    }

    function testSaveFail()
    {
        $image = $this->getMockedImage();

        $image->getToolkit()->shouldReceive('save')->andReturn(false);

        $this->assertFalse($image->save());
    }

    function testGetMime()
    {
        $image = $this->getImage();
        $this->assertEquals('image/png', $image->getMimeType());
    }

    /**
     * @expectedException \Exception
     */
    function testLoadFail()
    {
        $this->getImageFolder();
        $original_file = vfsStream::url('root/images') . '/' . $this->getDummyImageName();

        $toolkit = $this->getMockedToolkit();
        $toolkit->shouldReceive('load')->andReturn(false);

        new Image($original_file, $toolkit);
    }
}


