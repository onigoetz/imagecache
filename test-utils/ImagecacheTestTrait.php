<?php namespace Onigoetz\ImagecacheUtils;

use org\bovigo\vfs\vfsStream;

trait ImagecacheTestTrait
{
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    protected $vfsRoot;

    public function getDummyImageName()
    {
        return '500px-Smiley.png';
    }

    public function getImageFolder()
    {
        $this->vfsRoot = vfsStream::setup('root');
        vfsStream::copyFromFileSystem(__DIR__ . '/Fixtures/source');

        return vfsStream::url('root');
    }
}
