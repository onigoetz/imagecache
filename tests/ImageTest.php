<?php

use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\Point;
use Imagine\Image\PointInterface;
use Mockery as m;
use Onigoetz\Imagecache\Image;
use org\bovigo\vfs\vfsStream;

class ImageTest extends ImagecacheTestCase
{
    function getImage()
    {
        $this->getImageFolder();
        $original_file = vfsStream::url('root/images') . '/' . $this->getDummyImageName();

        return new Image($original_file);
    }

    /**
     * @expectedException \Onigoetz\Imagecache\Exceptions\NotFoundException
     */
    function testFileNotFound()
    {
        $this->assertFalse(new Image('/this/file/doesnt_exist'));
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

    function providerScaleAndCrop() {
        return [
            [new Box(300, 200), new Box(100, 100), new Box(150, 100), new Point(25, 0)],
            [new Box(252, 150), new Box(100, 100), new Box(168, 100), new Point(34, 0)],
            [new Box(200, 400), new Box(100, 100), new Box(100, 200), new Point(0, 50)],
        ];
    }

    /**
     * @dataProvider providerScaleAndCrop
     */
    function testScale_and_cropResize($originalImageSize, $resizeDestination, $scaled, $position)
    {
        $image = $this->getImage();
        $image->setImage($mockedImage = m::mock($image->getImage()));

        $scaledMatcher = function(BoxInterface $size) use ($scaled) {
            $this->assertEquals(strval($scaled), strval($size));
            return strval($size) == strval($scaled);
        };

        $resizeMatcher = function(BoxInterface $size) use ($resizeDestination) {
            $this->assertEquals(strval($resizeDestination), strval($size));
            return strval($resizeDestination) == strval($size);
        };

        $pointMatcher = function(PointInterface $point) use ($position) {
            $this->assertEquals(strval($position), strval($point));
            return strval($position) == strval($point);
        };

        $mockedImage->shouldReceive('getSize')->andReturn($originalImageSize);
        $mockedImage->shouldReceive('crop')->with(m::on($pointMatcher), m::on($resizeMatcher));
        $mockedImage->shouldReceive('resize')->with(m::on($scaledMatcher));

        $image->scale_and_crop($resizeDestination->getWidth(), $resizeDestination->getHeight());
    }

    function testRotateRandom()
    {
        $variation = 20;

        $matcher = function($val) use ($variation) {
            return ($val >= ($variation*-1) && $val <= $variation);
        };

        $image = $this->getImage();
        $image->setImage($mockedImage = m::mock($image->getImage()));

        $mockedImage->shouldReceive('rotate')->with(m::on($matcher), m::type('Imagine\Image\Palette\Color\ColorInterface'));

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

    function testGetInfo()
    {
        $this->getImageFolder();
        $original_file = vfsStream::url('root/images') . '/' . $this->getDummyImageName();

        $image =  new Image($original_file);

        $this->assertEquals(
            ['width' => 500, 'height' => 500, 'file_size' => filesize($original_file)],
            $image->getInfo()
        );
    }

    function testSaveInPlace()
    {
        $image = $this->getImage();

        $original_size = $image->getFileSize();

        $image->scale_and_crop(300, 300);
        $image->save();

        $this->assertFalse($image->getFileSize() == $original_size);
    }

    /**
     * @expectedException \RuntimeException
     */
    function testSaveFail()
    {
        $image = $this->getImage();
        $image->setImage($mockedImage = m::mock($image->getImage()));

        $mockedImage->shouldReceive('save')->andThrow(new \RuntimeException());

        $image->save();
    }
}


