<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache\Loader;

use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class LoaderTest extends TestCase
{
    public function testLoad()
    {
        $loader = $this->getMockForAbstractClass('YahooJapan\ConfigCacheBundle\ConfigCache\Loader\Loader');
        $loader
            ->expects($this->once())
            ->method('loadFile')
            ->willReturn(array('aaa' => 'bbb'))
            ;
        $this->assertSame(array('aaa' => 'bbb'), $loader->load('test'));
    }

    public function testAddLoader()
    {
        $arrayLoader = $this->getMock('YahooJapan\ConfigCacheBundle\ConfigCache\Loader\ArrayLoaderInterface');
        $loader      = $this->getMockForAbstractClass('YahooJapan\ConfigCacheBundle\ConfigCache\Loader\Loader');
        $loader->addLoader($arrayLoader);
        $actual = $this->util->getProperty($loader, 'loaders');
        if (isset($actual[0])) {
            $this->assertSame($arrayLoader, $actual[0]);
        } else {
            $this->fail('Unexpected addLoader.');
        }
    }

    public function testAddLoaders()
    {
        $arrayLoader1 = $this->getMock('YahooJapan\ConfigCacheBundle\ConfigCache\Loader\ArrayLoaderInterface');
        $arrayLoader2 = $this->getMock('YahooJapan\ConfigCacheBundle\ConfigCache\Loader\ArrayLoaderInterface');
        $loader      = $this->getMockForAbstractClass('YahooJapan\ConfigCacheBundle\ConfigCache\Loader\Loader');
        $loader->addLoaders(array($arrayLoader1, $arrayLoader2));
        $actual = $this->util->getProperty($loader, 'loaders');
        if (isset($actual[0]) && isset($actual[1])) {
            $this->assertSame($arrayLoader1, $actual[0]);
            $this->assertSame($arrayLoader2, $actual[1]);
        } else {
            $this->fail('Unexpected addLoaders.');
        }
    }

    public function testGetResolver()
    {
        $loader   = $this->getMockForAbstractClass('YahooJapan\ConfigCacheBundle\ConfigCache\Loader\Loader');
        $resolver = $this->getMock('Symfony\Component\Config\Loader\LoaderResolverInterface');
        $loader->setResolver($resolver);
        $this->assertSame($resolver, $loader->getResolver());
    }
}
