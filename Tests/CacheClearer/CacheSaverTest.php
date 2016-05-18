<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\CacheClearer;

use YahooJapan\ConfigCacheBundle\CacheClearer\CacheSaver;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class CacheSaverTest extends TestCase
{
    protected $saver;

    protected function setUp()
    {
        parent::setUp();

        $this->saver = new CacheSaver();
    }

    /**
     * Only assert calling "save" method.
     */
    public function testClear()
    {
        for ($i = 0; $i < 2; $i++) {
            $configCache = $this->createConfigCacheMock(array('save'));
            $configCache
                ->expects($this->once())
                ->method('save')
                ;
            $this->saver->addConfig($configCache);
        }
        $this->saver->clear(__DIR__);
    }

    public function testIsOptional()
    {
        $this->assertTrue($this->saver->isOptional());
    }

    public function testAddConfig()
    {
        $configCache = $this->createConfigCacheMock();
        $this->saver->addConfig($configCache);
        $this->assertSame(array($configCache), $this->util->getProperty($this->saver, 'configs'));
    }

    protected function createConfigCacheMock(array $methods = null)
    {
        return $this->util->createMock('YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache', $methods);
    }
}
