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

use YahooJapan\ConfigCacheBundle\CacheWarmer\CacheWarmer;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class CacheWarmerTest extends TestCase
{
    protected $warmer;

    protected function setUp()
    {
        parent::setUp();

        $this->warmer = new CacheWarmer();
    }

    /**
     * Only assert calling "create" method.
     */
    public function testWarmUp()
    {
        for ($i = 0; $i < 2; $i++) {
            $configCache = $this->createConfigCacheMock(array('create'));
            $configCache
                ->expects($this->once())
                ->method('create')
                ;
            $this->warmer->addConfig($configCache);
        }
        $this->warmer->warmUp(__DIR__);
    }

    public function testIsOptional()
    {
        $this->assertTrue($this->warmer->isOptional());
    }

    public function testAddConfig()
    {
        $configCache = $this->createConfigCacheMock();
        $this->warmer->addConfig($configCache);
        $this->assertSame(array($configCache), $this->util->getProperty($this->warmer, 'configs'));
    }

    protected function createConfigCacheMock(array $methods = null)
    {
        return $this->util->createMock('YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache', $methods);
    }
}
