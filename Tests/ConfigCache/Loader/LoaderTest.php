<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
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
        $loader = $this->createLoaderMock(array('loadFile'));
        $loader
            ->expects($this->once())
            ->method('loadFile')
            ->willReturn(array('aaa' => 'bbb'))
            ;
        $this->assertSame(array('aaa' => 'bbb'), $loader->load('test'));
    }

    public function testAddLoader()
    {
        $arrayLoader = $this->createArrayLoaderMock();
        $loader      = $this->createLoaderMock();
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
        $arrayLoader1 = $this->createArrayLoaderMock();
        $arrayLoader2 = $this->createArrayLoaderMock();
        $loader       = $this->createLoaderMock();
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
        $loader   = $this->createLoaderMock();
        $resolver = $this->util->createInterfaceMock('Symfony\Component\Config\Loader\LoaderResolverInterface');
        $loader->setResolver($resolver);
        $this->assertSame($resolver, $loader->getResolver());
    }

    protected function createLoaderMock(array $methods = null)
    {
        return $this->util->createAbstractMock('YahooJapan\ConfigCacheBundle\ConfigCache\Loader\Loader', $methods);
    }

    protected function createArrayLoaderMock()
    {
        return $this->util->createInterfaceMock('YahooJapan\ConfigCacheBundle\ConfigCache\Loader\ArrayLoaderInterface');
    }
}
