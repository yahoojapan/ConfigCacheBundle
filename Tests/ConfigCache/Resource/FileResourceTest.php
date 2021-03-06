<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache\Resource;

use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\FileResource;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\FileResourceConfiguration;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class FileResourceTest extends TestCase
{
    public function testConstruct()
    {
        $className = 'YahooJapan\ConfigCacheBundle\ConfigCache\Resource\FileResource';
        $resource  = $this->createFileResourceMock();
        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();
        // assert OK with no Configuration
        $this->assertNull($constructor->invoke($resource, __DIR__.'/../../Fixtures/test_service1.yml'));
        $this->assertNull($constructor->invoke(
            $resource,
            __DIR__.'/../../Fixtures/test_service1.yml',
            new FileResourceConfiguration(),
            'test_alias',
            true
        ));
    }

    public function testCreate()
    {
        $path          = __DIR__.'/../../Fixtures/test_service1.yml';
        $configuration = new FileResourceConfiguration();
        $alias         = 'test_alias';
        $resource      = FileResource::create($path, $configuration, $alias, true);
        $this->assertSame($path, $resource->getResource());
        $this->assertSame($configuration, $resource->getConfiguration());
        $this->assertSame($alias, $resource->getAlias());
        $this->assertTrue($resource->isRestorable());
    }

    /**
     * @dataProvider existsProvider
     */
    public function testExists($file, $expected)
    {
        $resource = new FileResource($file, new FileResourceConfiguration());
        $this->assertSame($expected, $resource->exists());
    }

    /**
     * @return array ($file, $expected)
     */
    public function existsProvider()
    {
        return array(
            // has no file
            array(__DIR__.'/../../Fixtures/no_exists.yml', false),
            // has file
            array(__DIR__.'/../../Fixtures/test_service1.yml', true),
        );
    }

    public function testSetConfiguration()
    {
        $resource = $this->createFileResourceMock();
        $configuration = $this->util->createInterfaceMock('Symfony\Component\Config\Definition\ConfigurationInterface');
        $this->assertSame($configuration, $resource->setConfiguration($configuration)->getConfiguration());
    }

    public function testSetResource()
    {
        $resource = $this->createFileResourceMock();
        $dir = __DIR__.'/../../Fixtures';
        $this->assertSame($dir, $resource->setResource($dir)->getResource());
    }

    public function testGetAlias()
    {
        $resource = $this->createFileResourceMock();
        $this->assertSame(null, $resource->getAlias());
        $resource->setAlias($expected = 'test');
        $this->assertSame($expected, $resource->getAlias());
    }

    /**
     * @dataProvider setAliasProvider
     */
    public function testSetAlias($alias, $expectedException)
    {
        $resource = $this->createFileResourceMock();
        if (!is_null($expectedException)) {
            $this->setExpectedException($expectedException);
        }
        $this->assertSame($resource, $resource->setAlias($alias));
        $this->assertSame($alias, $resource->getAlias());
    }

    public function testHasAlias()
    {
        $resource = $this->createFileResourceMock();
        $this->assertFalse($resource->hasAlias());
        $resource->setAlias('');
        $this->assertFalse($resource->hasAlias());
        $resource->setAlias('test');
        $this->assertTrue($resource->hasAlias());
    }

    /**
     * @return array($alias, $expectedException)
     */
    public function setAliasProvider()
    {
        return array(
            // normal
            array('test_name', null),
            array('', null),
            // exception
            array(false, '\InvalidArgumentException'),
            array(null, '\InvalidArgumentException'),
        );
    }

    /**
     * Tests isRestorable() and setRestorable()
     */
    public function testRestorable()
    {
        $resource = $this->createFileResourceMock();
        $this->assertFalse($resource->isRestorable());
        $resource->setRestorable(true);
        $this->assertTrue($resource->isRestorable());
    }

    protected function createFileResourceMock(array $methods = null)
    {
        return $this->util->createMock('YahooJapan\ConfigCacheBundle\ConfigCache\Resource\FileResource', $methods);
    }
}
