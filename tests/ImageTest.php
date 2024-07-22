<?php namespace Onigoetz\ImagecacheTests;

use Mockery as m;
use Onigoetz\Imagecache\Image;
use Onigoetz\ImagecacheUtils\ImagecacheTestCase;
use org\bovigo\vfs\vfsStream;

class ImageTest extends ImagecacheTestCase
{
    public function getImage()
    {
        $this->getImageFolder();
        $original_file = vfsStream::url('root') . '/' . $this->getDummyImageName();

        return new Image($original_file);
    }

    public function testFileNotFound()
    {
        $this->expectException(\Onigoetz\Imagecache\Exceptions\NotFoundException::class);
        $this->assertFalse(new Image('/this/file/doesnt_exist'));
    }

    public function testScale_and_cropNeedsWidth()
    {
        $this->expectException(\LogicException::class);
        $image = $this->getImage();

        $image->scale_and_crop(null, 300);
    }

    public function testScale_and_cropNeedsHeight()
    {
        $this->expectException(\LogicException::class);
        $image = $this->getImage();

        $image->scale_and_crop(300, null);
    }

    public static function providerScaleAndCrop()
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

        $mockedImage->expects()->width()->twice()->andReturn($originalImageSize['width']);
        $mockedImage->expects()->height()->twice()->andReturn($originalImageSize['height']);

        $mockedImage->expects()->resize($scaled['width'], $scaled['height']);
        $mockedImage->expects()->crop(
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

        $mockedImage->expects()
            ->rotate(m::on($matcher), m::any());

        $image->rotate($variation, null, true);
    }

    public function testCropNeedsWidth()
    {
        $this->expectException(\LogicException::class);
        $image = $this->getImage();

        $image->crop(0, 0, null, 0);
    }

    public function testCropNeedsHeight()
    {
        $this->expectException(\LogicException::class);
        $image = $this->getImage();

        $image->crop(0, 0, 0, null);
    }

    public function testCropNeedsXOffset()
    {
        $this->expectException(\LogicException::class);
        $image = $this->getImage();

        $image->crop(null, 0, 300, 300);
    }

    public function testCropNeedsYOffset()
    {
        $this->expectException(\LogicException::class);
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

    public function testSaveFail()
    {
        $this->expectException(\RuntimeException::class);
        $image = $this->getImage();
        $image->setImage($mockedImage = m::mock($image->getImage()));

        $mockedImage->shouldReceive('save')->andThrow(new \RuntimeException());

        $image->save();
    }
}
