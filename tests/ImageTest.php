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
    public function getImage()
    {
        $this->getImageFolder();
        $original_file = vfsStream::url('root') . '/' . $this->getDummyImageName();

        return new Image($original_file);
    }

    /**
     * @expectedException \Onigoetz\Imagecache\Exceptions\NotFoundException
     */
    public function testFileNotFound()
    {
        $this->assertFalse(new Image('/this/file/doesnt_exist'));
    }

    /**
     * @expectedException \LogicException
     */
    public function testScale_and_cropNeedsWidth()
    {
        $image = $this->getImage();

        $image->scale_and_crop(null, 300);
    }

    /**
     * @expectedException \LogicException
     */
    public function testScale_and_cropNeedsHeight()
    {
        $image = $this->getImage();

        $image->scale_and_crop(300, null);
    }

    public function providerScaleAndCrop()
    {
        return [
            [new Box(300, 200), new Box(100, 100), new Box(150, 100), new Point(25, 0)],
            [new Box(252, 150), new Box(100, 100), new Box(168, 100), new Point(34, 0)],
            [new Box(200, 400), new Box(100, 100), new Box(100, 200), new Point(0, 50)],
            [new Box(797, 1100), new Box(60, 75), new Box(60, 83), new Point(0, 4)],
        ];
    }

    /**
     * @dataProvider providerScaleAndCrop
     */
    public function testScale_and_cropResize($originalImageSize, $resizeDestination, $scaled, $position)
    {
        $image = $this->getImage();
        $image->setImage($mockedImage = m::mock($image->getImage()));

        $scaledMatcher = function (BoxInterface $size) use ($scaled) {
            $this->assertEquals(strval($scaled), strval($size));

            return strval($size) == strval($scaled);
        };

        $resizeMatcher = function (BoxInterface $size) use ($resizeDestination) {
            $this->assertEquals(strval($resizeDestination), strval($size));

            return strval($resizeDestination) == strval($size);
        };

        $pointMatcher = function (PointInterface $point) use ($position) {
            $this->assertEquals(strval($position), strval($point));

            return strval($position) == strval($point);
        };

        $mockedImage->shouldReceive('getSize')->andReturn($originalImageSize);
        $mockedImage->shouldReceive('crop')->with(m::on($pointMatcher), m::on($resizeMatcher));
        $mockedImage->shouldReceive('resize')->with(m::on($scaledMatcher));

        $image->scale_and_crop($resizeDestination->getWidth(), $resizeDestination->getHeight());
    }

    public function testRotateRandom()
    {
        $variation = 20;

        $matcher = function ($val) use ($variation) {
            return $val >= ($variation * -1) && $val <= $variation;
        };

        $image = $this->getImage();
        $image->setImage($mockedImage = m::mock($image->getImage()));

        $mockedImage->shouldReceive('rotate')->with(m::on($matcher), m::type('Imagine\Image\Palette\Color\ColorInterface'));

        $image->rotate($variation, null, true);
    }

    /**
     * @expectedException \LogicException
     */
    public function testCropNeedsWidth()
    {
        $image = $this->getImage();

        $image->crop(0, 0, null, 0);
    }

    /**
     * @expectedException \LogicException
     */
    public function testCropNeedsHeight()
    {
        $image = $this->getImage();

        $image->crop(0, 0, 0, null);
    }

    /**
     * @expectedException \LogicException
     */
    public function testCropNeedsXOffset()
    {
        $image = $this->getImage();

        $image->crop(null, 0, 300, 300);
    }

    /**
     * @expectedException \LogicException
     */
    public function testCropNeedsYOffset()
    {
        $image = $this->getImage();

        $image->crop(0, null, 300, 300);
    }

    public function testSave()
    {
        $image = $this->getImage();
        $final_file = vfsStream::url('root') . '/test-save.png';

        $this->assertFalse(file_exists($final_file));
        $image->save($final_file);
        $this->assertTrue(file_exists($final_file));
    }

    public function testGetInfo()
    {
        $this->getImageFolder();
        $original_file = vfsStream::url('root') . '/' . $this->getDummyImageName();

        $image =  new Image($original_file);

        $this->assertEquals(
            ['width' => 500, 'height' => 500, 'file_size' => filesize($original_file)],
            $image->getInfo()
        );
    }

    public function testSaveInPlace()
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
    public function testSaveFail()
    {
        $image = $this->getImage();
        $image->setImage($mockedImage = m::mock($image->getImage()));

        $mockedImage->shouldReceive('save')->andThrow(new \RuntimeException());

        $image->save();
    }
}
