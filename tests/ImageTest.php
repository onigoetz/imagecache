<?php

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
            [
                ['width' => 300, 'height' => 200],
                ['width' => 100, 'height' => 100],
                ['width' => 150, 'height' => 100],
                ['x' => 25, 'y' => 0]
            ],
            [
                ['width' => 252, 'height' => 150],
                ['width' => 100, 'height' => 100],
                ['width' => 168, 'height' => 100],
                ['x' => 34, 'y' => 0]
            ],
            [
                ['width' => 200, 'height' => 400],
                ['width' => 100, 'height' => 100],
                ['width' => 100, 'height' => 200],
                ['x' => 0, 'y' => 50]
            ],
            [
                ['width' => 797, 'height' => 1100],
                ['width' => 60, 'height' => 75],
                ['width' => 60, 'height' => 83],
                ['x' => 0, 'y' => 4]
            ],
        ];
    }

    /**
     * @dataProvider providerScaleAndCrop
     */
    public function testScale_and_cropResize($originalImageSize, $resizeDestination, $scaled, $position)
    {
        $image = $this->getImage();
        $image->setImage($mockedImage = m::mock($image->getImage()));

        $mockedImage->shouldReceive('getWidth')->andReturn($originalImageSize['width']);
        $mockedImage->shouldReceive('getHeight')->andReturn($originalImageSize['height']);

        $mockedImage->shouldReceive('resize')->with($scaled['width'], $scaled['height']);
        $mockedImage->shouldReceive('crop')->with(
            $resizeDestination['width'],
            $resizeDestination['height'],
            $position['x'],
            $position['y']
        );

        $image->scale_and_crop($resizeDestination['width'], $resizeDestination['height']);
    }

    public function testRotateRandom()
    {
        $variation = 20;

        $matcher = function ($val) use ($variation) {
            return $val >= ($variation * -1) && $val <= $variation;
        };

        $image = $this->getImage();
        $image->setImage($mockedImage = m::mock($image->getImage()));

        $mockedImage->shouldReceive('rotate')->with(m::on($matcher), m::any());

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
