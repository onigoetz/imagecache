<?php

use Onigoetz\Imagecache\Manager;
use org\bovigo\vfs\vfsStream;
use Mockery as m;

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
        return 'gd';
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
