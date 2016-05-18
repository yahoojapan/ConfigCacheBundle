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
use YahooJapan\ConfigCacheBundle\ConfigCache\SaveAreaBuilder;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class CacheCleanupTest extends TestCase
{
    protected $builder;
    protected $filesystem;

    protected function setUp()
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->builder    = new SaveAreaBuilder('test', $this->filesystem);
        $this->cleanup    = new CacheCleanup($this->builder, $this->filesystem);
    }

    public function testWarmUp()
    {
        $tempDirectory = $this->builder->buildPrefix();
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
