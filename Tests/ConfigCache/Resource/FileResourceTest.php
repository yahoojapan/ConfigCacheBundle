<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache\Resource;

use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\FileResource;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\FileResourceConfiguration;

class FileResourceTest extends \PHPUnit_Framework_TestCase
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
            new FileResourceConfiguration()
        ));
    }

    public function testCreate()
    {
        $path          = __DIR__.'/../../Fixtures/test_service1.yml';
        $configuration = new FileResourceConfiguration();
        $alias         = 'test_alias';
        $resource      = FileResource::create($path, $configuration, $alias);
        $this->assertSame($path, $resource->getResource());
        $this->assertSame($configuration, $resource->getConfiguration());
        $this->assertSame($alias, $resource->getAlias());
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
        $configuration = $this->getMock('Symfony\Component\Config\Definition\ConfigurationInterface');
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

    protected function createFileResourceMock(array $methods = null)
    {
        return $this->createMock('YahooJapan\ConfigCacheBundle\ConfigCache\Resource\FileResource', $methods);
    }

    protected function createMock($name, array $methods = null)
    {
        $mock = $this->getMockBuilder($name)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock()
            ;

        return $mock;
    }
}
