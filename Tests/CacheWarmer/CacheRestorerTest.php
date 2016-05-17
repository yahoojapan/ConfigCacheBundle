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

use YahooJapan\ConfigCacheBundle\CacheWarmer\CacheRestorer;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class CacheRestorerTest extends TestCase
{
    protected $restorer;

    protected function setUp()
    {
        parent::setUp();

        $this->restorer = new CacheRestorer();
    }

    /**
     * Only assert calling "restore" method.
     */
    public function testWarmUp()
    {
        for ($i = 0; $i < 2; $i++) {
            $configCache = $this->createConfigCacheMock(array('restore'));
            $configCache
                ->expects($this->once())
                ->method('restore')
                ;
            $this->restorer->addConfig($configCache);
        }
        $this->restorer->warmUp(__DIR__);
    }

    public function testIsOptional()
    {
        $this->assertTrue($this->restorer->isOptional());
    }

    public function testAddConfig()
    {
        $configCache = $this->createConfigCacheMock();
        $this->restorer->addConfig($configCache);
        $this->assertSame(array($configCache), $this->util->getProperty($this->restorer, 'configs'));
    }

    protected function createConfigCacheMock(array $methods = null)
    {
        return $this->util->createMock('YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache', $methods);
    }
}
