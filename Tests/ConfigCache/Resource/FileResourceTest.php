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
        $resource  = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock()
            ;
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
        $resource = $this->getMockBuilder('YahooJapan\ConfigCacheBundle\ConfigCache\Resource\FileResource')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock()
            ;
        $configuration = $this->getMock('Symfony\Component\Config\Definition\ConfigurationInterface');
        $this->assertSame($configuration, $resource->setConfiguration($configuration)->getConfiguration());
    }

    public function testSetResource()
    {
        $resource = $this->getMockBuilder('YahooJapan\ConfigCacheBundle\ConfigCache\Resource\FileResource')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock()
            ;
        $dir = __DIR__.'/../../Fixtures';
        $this->assertSame($dir, $resource->setResource($dir)->getResource());
    }
}
