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
        $this->markTestSkipped('Class Image needs to be mockable in an easier way');

        $image = $this->getMockedImage();

        var_dump($image->getToolkit());

        $image->getToolkit()->shouldReceive('resize')->andReturn('false');

        $this->assertFalse($image->scale_and_crop(300, 300));
    }

    function testRotateRandom()
    {
        $this->markTestSkipped('Class Image needs to be mockable in an easier way');

        $image = $this->getMockedImage();

        $image->rotate(0);
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
}


