<?php

include dirname(__DIR__) . '/vendor/autoload.php';

use Mockery as m;
use Onigoetz\Imagecache\Manager;
use org\bovigo\vfs\vfsStream;

abstract class ImagecacheTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    protected $vfsRoot;

    public function tearDown()
    {
        m::close();
    }

    function getManager($options = array())
    {
        //Add default option
        $options += array('path_images_root' => $this->getImageFolder());

        return new Manager($options);
    }

    function getMockedManager($options = array())
    {
        //Add default option
        $options += array('path_images_root' => $this->getImageFolder());

        return m::mock('Onigoetz\Imagecache\Manager', array($options))
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
    }

    function getImageFolder()
    {
        $this->vfsRoot = vfsStream::setup('root');
        mkdir(vfsStream::url('root') . '/images');
        vfsStream::copyFromFileSystem(__DIR__ . '/Fixtures/source', $this->vfsRoot->getChild('images'));
        return vfsStream::url('root');
    }

    function getDummyImageName()
    {
        return '500px-Smiley.png';
    }
}

/**
 * Perceptual image diff
 * http://www.phpied.com/image-diff/
 */
class ImageCompare
{
    public static function compare($res1, $res2)
    {
        // create images
        $i1 = @imagecreatefromstring(file_get_contents($res1));
        $i2 = @imagecreatefromstring(file_get_contents($res2));

        // check if we were given garbage
        if (!$i1) {
            throw new Exception("$res1 is not a valid image");
        }

        if (!$i2) {
            throw new Exception("$res2 is not a valid image");
        }

        // dimensions of the first image
        $sx1 = imagesx($i1);
        $sy1 = imagesy($i1);

        // compare dimensions
        if ($sx1 !== imagesx($i2) || $sy1 !== imagesy($i2)) {
            throw new Exception("The images are not even the same size");
        }

        // create a diff image
        $diffi = imagecreatetruecolor($sx1, $sy1);
        $green = imagecolorallocate($diffi, 0, 255, 0);
        imagefill($diffi, 0, 0, imagecolorallocate($diffi, 0, 0, 0));

        // increment this counter when encountering a pixel diff
        $different_pixels = 0;

        // loop x and y
        for ($x = 0; $x < $sx1; $x++) {
            for ($y = 0; $y < $sy1; $y++) {

                $rgb1 = imagecolorat($i1, $x, $y);
                $pix1 = imagecolorsforindex($i1, $rgb1);

                $rgb2 = imagecolorat($i2, $x, $y);
                $pix2 = imagecolorsforindex($i2, $rgb2);

                if (($rgb1 & 0x7F000000) >> 24 != 127 && ($rgb2 & 0x7F000000) >> 24 != 127 && $pix1 !== $pix2) { // different pixel
                    // increment and paint in the diff image
                    $different_pixels++;
                    imagesetpixel($diffi, $x, $y, $green);
                }

            }
        }

        if (!$different_pixels) {
            return 100;
        }

        imagepng($diffi, "{$res1}_diff.png");

        $total = $sx1 * $sy1;
        return 100 - number_format(100 * $different_pixels / $total, 2);
    }
}
