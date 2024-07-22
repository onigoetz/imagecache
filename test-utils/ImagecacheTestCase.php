<?php namespace Onigoetz\ImagecacheUtils;

use Mockery as m;
use Onigoetz\Imagecache\Manager;
use Onigoetz\ImagecacheUtils\ImagecacheTestTrait;
use org\bovigo\vfs\vfsStream;

use Mockery\Adapter\Phpunit\MockeryTestCase;

abstract class ImagecacheTestCase extends MockeryTestCase
{
    use ImagecacheTestTrait;

    public function getManager($options = [])
    {
        //Add default option
        $options += ['path_local' => $this->getImageFolder()];

        return new Manager($options);
    }

    public function getMockedManager($options = [])
    {
        //Add default option
        $options += ['path_local' => $this->getImageFolder()];

        return m::mock('Onigoetz\Imagecache\Manager', [$options])
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
    }
}
