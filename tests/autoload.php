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
