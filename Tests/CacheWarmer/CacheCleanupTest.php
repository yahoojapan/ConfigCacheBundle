<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\CacheWarmer;

use Symfony\Component\Filesystem\Filesystem;
use YahooJapan\ConfigCacheBundle\CacheWarmer\CacheCleanup;
use YahooJapan\ConfigCacheBundle\ConfigCache\RestorablePhpFileCache;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class CacheCleanupTest extends TestCase
{
    protected $filesystem;
    protected $cleanup;

    protected function setUp()
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->cleanup    = new CacheCleanup('test', $this->filesystem);
    }

    public function testWarmUp()
    {
        $tempDirectory = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .RestorablePhpFileCache::TEMP_DIRECTORY_PREFIX
            .$this->util->getProperty($this->cleanup, 'env')
            ;
        $this->filesystem->mkdir($tempDirectory);
        $this->assertTrue($this->filesystem->exists($tempDirectory));
        $this->cleanup->warmUp(__DIR__);
        $this->assertFalse($this->filesystem->exists($tempDirectory));
    }

    public function testIsOptional()
    {
        $this->assertTrue($this->cleanup->isOptional());
    }
}
