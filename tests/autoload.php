<?php

include dirname(__DIR__) . '/vendor/autoload.php';

use Mockery as m;
use Onigoetz\Imagecache\Imagekit\Gd;
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

    function getMockedToolkit()
    {
        return m::mock(new Gd);
    }

    function getManager($options = array(), $toolkit = null)
    {
        if ($toolkit == null) {
            $toolkit = $this->getMockedToolkit();
        }

        //Add default option
        $options += array('path_images_root' => $this->getImageFolder());

        return new Manager($options, $toolkit);
    }

    function getMockedManager($options = array(), $toolkit = null)
    {

        if ($toolkit == null) {
            $toolkit = $this->getMockedToolkit();
        }

        //Add default option
        $options += array('path_images_root' => $this->getImageFolder());

        return m::mock('Onigoetz\Imagecache\Manager', array($options, $toolkit))
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

/*
	PHasher is a naive perceptual hashing class for PHP.
	@url https://raw.github.com/kennethrapp/phasher/master/phasher.class.php
*/

class PHasher
{

    /* hash two images and return an index of their similarty as a percentage. */
    public static function compare($res1, $res2, $precision = 1)
    {

        $hash1 = self::hashImage($res1); // this one should never be rotated
        $hash2 = self::hashImage($res2);

        $similarity = count($hash1);

        // take the hamming distance between the hashes.
        foreach ($hash1 as $key => $val) {
            if ($hash1[$key] != $hash2[$key]) {
                $similarity--;
            }
        }
        $percentage = round(($similarity / count($hash1) * 100), $precision);
        return $percentage;
    }

    public static function arrayAverage($arr)
    {
        return floor(array_sum($arr) / count($arr));
    }

    /* build a perceptual hash out of an image. Just uses averaging because it's faster.
        also we're storing the hash as an array of bits instead of a string.
        http://www.hackerfactor.com/blog/index.php?/archives/432-Looks-Like-It.html */

    public static function hashImage($res, $size = 8)
    {

        $res = imagecreatefromstring(file_get_contents($res)); // make sure this is a resource
        $rescached = imagecreatetruecolor($size, $size);

        imagecopyresampled($rescached, $res, 0, 0, 0, 0, $size, $size, imagesx($res), imagesy($res));
        imagecopymergegray($rescached, $res, 0, 0, 0, 0, $size, $size, 50);

        $w = imagesx($rescached);
        $h = imagesy($rescached);

        $pixels = array();

        for ($y = 0; $y < $size; $y++) {

            for ($x = 0; $x < $size; $x++) {
                $rgb = imagecolorsforindex($rescached, imagecolorat($rescached, $x, $y));

                $r = $rgb['red'];
                $g = $rgb['green'];
                $b = $rgb['blue'];

                $gs = (($r * 0.299) + ($g * 0.587) + ($b * 0.114));
                $gs = floor($gs);

                $pixels[] = $gs;
            }
        }

        // find the average value in the array
        $avg = self::arrayAverage($pixels);

        // create a hash (1 for pixels above the mean, 0 for average or below)
        $index = 0;

        foreach ($pixels as $px) {
            $hash[$index] = ($px > $avg);
            $index += 1;
        }

        return $hash;
    }
}
