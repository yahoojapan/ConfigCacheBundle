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

use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\DirectoryResource;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\DirectoryResourceConfiguration;

class DirectoryResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $className = 'YahooJapan\ConfigCacheBundle\ConfigCache\Resource\DirectoryResource';
        $resource  = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock()
            ;
        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();
        // assert OK with no Configuration
        $this->assertNull($constructor->invoke($resource, __DIR__.'/../../Fixtures'));
        $this->assertNull($constructor->invoke(
            $resource,
            __DIR__.'/../../Fixtures',
            new DirectoryResourceConfiguration()
        ));
    }

    /**
     * @dataProvider existsProvider
     */
    public function testExists($file, $expected)
    {
        $resource = new DirectoryResource($file, new DirectoryResourceConfiguration());
        $this->assertSame($expected, $resource->exists());
    }

    /**
     * @return array ($file, $expected)
     */
    public function existsProvider()
    {
        return array(
            // has no directory
            array(__DIR__.'/../../Fixtures/no_exists', false),
            // has directory
            array(__DIR__.'/../../Fixtures', true),
        );
    }

    public function testSetConfiguration()
    {
        $resource = $this->getMockBuilder('YahooJapan\ConfigCacheBundle\ConfigCache\Resource\DirectoryResource')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock()
            ;
        $configuration = $this->getMock('Symfony\Component\Config\Definition\ConfigurationInterface');
        $this->assertSame($configuration, $resource->setConfiguration($configuration)->getConfiguration());
    }

    public function testSetResource()
    {
        $resource = $this->getMockBuilder('YahooJapan\ConfigCacheBundle\ConfigCache\Resource\DirectoryResource')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock()
            ;
        $dir = __DIR__.'/../../Fixtures';
        $this->assertSame($dir, $resource->setResource($dir)->getResource());
    }
}
