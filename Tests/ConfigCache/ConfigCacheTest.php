<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache;

use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Finder\Finder;
use YahooJapan\ConfigCacheBundle\ConfigCache\Util\ArrayAccess;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\ConfigCacheConfiguration;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\ConfigCacheDeepConfiguration;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\ConfigCacheMasterConfiguration;

class ConfigCacheTest extends ConfigCacheTestCase
{
    public function testConstruct()
    {
        $cache       = $this->util->createAbstractMock('Doctrine\Common\Cache\Cache');
        $loader      = $this->util->createInterfaceMock('Symfony\Component\Config\Loader\LoaderInterface');
        $configCache = $this->createConfigCacheMock();
        $class       = new \ReflectionClass('YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache');
        $constructor = $class->getConstructor();
        $this->assertNull($constructor->invoke($configCache, $cache, $loader));
        $this->assertNull($constructor->invoke($configCache, $cache, $loader, array('aaa' => 'bbb')));
    }

    public function testFind()
    {
        // assert only calling findInterna, findAll
        $configCache = $this->createConfigCacheMock(array('findInternal', 'findAll'));
        $key = 'testKey';
        $default = array('default' => 'zzz');
        $findAllResult = array('aaa' => 'bbb');
        $configCache
            ->expects($this->exactly(2))
            ->method('findAll')
            ->willReturn($findAllResult)
            ;
        $configCache
            ->expects($this->exactly(2))
            ->method('findInternal')
            ->withConsecutive(
                array($findAllResult, $key),
                array($findAllResult, $key, $default)
            )
            ->willReturn(null)
            ;

        // default enabled or not
        $configCache->find($key);
        $configCache->find($key, $default);
    }

    /**
     * @dataProvider findAllProvider
     */
    public function testFindAll($resources, $delete, $expected)
    {
        $configuration = new ConfigCacheConfiguration();
        foreach ($resources as $resource) {
            self::$cache->addResource($resource, $configuration);
        }

        $this->assertSame($expected, self::$cache->findAll());
        $this->delete = $delete;
    }

    /**
     * @return array ($resources, $delete, $expected)
     */
    public function findAllProvider()
    {
        return array(
            // not created cache
            array(
                array(
                    __DIR__.'/../Fixtures/test_service2.yml',
                ),
                // will not be removed file
                false,
                array(
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                ),
            ),
            // created cache
            array(
                array(
                    __DIR__.'/../Fixtures/test_service1.yml',
                    __DIR__.'/../Fixtures/test_service2.yml',
                ),
                // will be removed file
                true,
                array(
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                ),
            ),
        );
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate($resources, $delete, $expectedFileName, $expectedData)
    {
        $configuration = new ConfigCacheConfiguration();
        foreach ($resources as $resource) {
            self::$cache->addResource($resource, $configuration);
        }

        self::$cache->create();
        $this->delete = $delete;

        $expectedFileNames = array($expectedFileName, $this->getHashFileName($expectedFileName));
        $finder = Finder::create()
            ->files()
            ->filter(function (\SplFileInfo $file) use ($expectedFileNames) {
                if (in_array($file->getFilename(), $expectedFileNames)) {
                    return true;
                }

                return false;
            })
            ->in((array) self::$tmpDir)
            ;
        foreach ($finder as $file) {
            $data = require $file->getRealPath();
            $this->assertSame($expectedData, $data['data']);
        }
    }

    /**
     * @return array ($resources, $delete, $expectedFileName, $expectedData)
     */
    public function createProvider()
    {
        return array(
            // not created cache
            array(
                array(
                    __DIR__.'/../Fixtures/test_service2.yml',
                ),
                // will not be removed file
                false,
                '[cache][1].php',
                array(
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                ),
            ),
            // created cache
            array(
                array(
                    __DIR__.'/../Fixtures/test_service1.yml',
                    __DIR__.'/../Fixtures/test_service2.yml',
                ),
                // will be removed file
                true,
                '[cache][1].php',
                array(
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                ),
            ),
        );
    }

    /**
     * @dataProvider createInternalProvider
     */
    public function testCreateInternal($resources, $expectedFileName, $expectedData)
    {
        $configuration = new ConfigCacheConfiguration();
        foreach ($resources as $resource) {
            self::$cache->addResource($resource, $configuration);
        }

        // assert return
        $this->assertSame($expectedData, $this->util->invoke(self::$cache, 'createInternal'));

        // assert file created and the same require data
        $expectedFileNames = array($expectedFileName, $this->getHashFileName($expectedFileName));
        $finder = Finder::create()
            ->files()
            ->filter(function (\SplFileInfo $file) use ($expectedFileNames) {
                if (in_array($file->getFilename(), $expectedFileNames)) {
                    return true;
                }

                return false;
            })
            ->in((array) self::$tmpDir)
            ;
        $this->assertSame(1, count($finder));
        foreach ($finder as $file) {
            $data = require $file->getRealPath();
            $this->assertSame($expectedData, $data['data']);
        }
    }

    /**
     * @return array ($resources, $expectedFileName, $expectedData)
     */
    public function createInternalProvider()
    {
        return array(
            array(
                array(
                    __DIR__.'/../Fixtures/test_service2.yml',
                ),
                // file name, data array
                '[cache][1].php',
                array(
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                ),
            ),
            array(
                array(
                    __DIR__.'/../Fixtures/test_service1.yml',
                    __DIR__.'/../Fixtures/test_service2.yml',
                ),
                // file name, data array
                '[cache][1].php',
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                ),
            ),
        );
    }

    public function testFindId()
    {
        $this->assertSame('cache', $this->util->invoke(self::$cache, 'findId'));
    }

    /**
     * @dataProvider findInternalProvider
     */
    public function testFindInternal($key, $resources, $useArrayAccess, $default, $expected)
    {
        if (!$useArrayAccess) {
            $configuration = new ConfigCacheConfiguration();
        } else {
            $configuration = new ConfigCacheDeepConfiguration();
            // only set when using ArrayAccess
            self::$cache->setArrayAccess(new ArrayAccess());
        }
        foreach ($resources as $resource) {
            self::$cache->addResource($resource, $configuration);
        }
        $args = array(self::$cache->findAll(), $key);
        if (!is_null($default)) {
            $args[] = $default;
        }
        $this->assertSame($expected, $this->util->invokeArgs(self::$cache, 'findInternal', $args));
    }

    /**
     * @return array ($key, $resources, $useArrayAccess, $default, $expected)
     */
    public function findInternalProvider()
    {
        return array(
            // has no ArrayAccess, has key
            array(
                'xxx',
                array(
                    __DIR__.'/../Fixtures/test_service1.yml',
                    __DIR__.'/../Fixtures/test_service2.yml',
                ),
                false,
                null,
                'yyy',
            ),
            // has no ArrayAccess, no key, no default
            array(
                '@@@',
                array(
                    __DIR__.'/../Fixtures/test_service1.yml',
                    __DIR__.'/../Fixtures/test_service2.yml',
                ),
                false,
                null,
                array(),
            ),
            // has no ArrayAccess, no key, has default
            array(
                '@@@',
                array(
                    __DIR__.'/../Fixtures/test_service1.yml',
                    __DIR__.'/../Fixtures/test_service2.yml',
                ),
                false,
                array('default'),
                array('default'),
            ),
            // has ArrayAccess, has key
            array(
                'aaa',
                array(__DIR__.'/../Fixtures/test_service_deep.yml'),
                true,
                null,
                'bbb',
            ),
            // has ArrayAccess, multi dimension
            array(
                'ccc.ccc_ccc.ccc_ccc_aaa',
                array(__DIR__.'/../Fixtures/test_service_deep.yml'),
                true,
                null,
                'bbb',
            ),
            // has ArrayAccess, has no key, no default
            array(
                '###',
                array(__DIR__.'/../Fixtures/test_service_deep.yml'),
                true,
                null,
                array(),
            ),
            // has ArrayAccess, has no key, has default
            array(
                '###',
                array(__DIR__.'/../Fixtures/test_service_deep.yml'),
                true,
                array('default'),
                array('default'),
            ),
        );
    }

    /**
     * @dataProvider loadProvider
     */
    public function testLoad($resources, $config, $masterConfiguration, $strict, $expected)
    {
        $configuration = new ConfigCacheConfiguration();
        foreach ($resources as $resource) {
            self::$cache->addResource($resource, $configuration);
        }
        $this->util->setProperty(self::$cache, 'config', $config);
        if (!is_null($masterConfiguration)) {
            self::$cache->setConfiguration($masterConfiguration);
        }
        if (!$strict) {
            self::$cache->setStrict($strict);
        }

        if (is_string($expected) && class_exists($expected)) {
            $this->setExpectedException($expected);
        }
        $this->assertSame($expected, $this->util->invoke(self::$cache, 'load'));
    }

    /**
     * @return array ($resources, $config, $masterConfiguration, $strict, $expected)
     */
    public function loadProvider()
    {
        return array(
            // resources zero
            array(
                array(),
                array(),
                null,
                true,
                'Exception',
            ),
            // resources one
            array(
                array(
                    __DIR__.'/../Fixtures/test_service1.yml',
                ),
                array(),
                null,
                true,
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                ),
            ),
            // resources one and strict = true
            array(
                array(
                    __DIR__.'/../Fixtures/test_service1.yml',
                ),
                array(),
                null,
                false,
                array(
                    // original config with root key
                    'test_service' => array(
                        'aaa' => 'bbb',
                        'ccc' => 'ddd',
                    ),
                ),
            ),
            // resources greater than two
            array(
                array(
                    __DIR__.'/../Fixtures/test_service1.yml',
                    __DIR__.'/../Fixtures/test_service2.yml',
                ),
                array(),
                null,
                true,
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                ),
            ),
            // enabled config
            array(
                array(
                    __DIR__.'/../Fixtures/test_service1.yml',
                ),
                array(
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                ),
                null,
                true,
                array(
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                ),
            ),
            // enabled master configuration
            array(
                array(
                    __DIR__.'/../Fixtures/test_service1.yml',
                ),
                array(
                    'eee' => 12345,
                    'ggg' => true,
                ),
                new ConfigCacheMasterConfiguration(),
                true,
                array(
                    'eee' => 12345,
                    'ggg' => true,
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                ),
            ),
        );
    }

    public function testLoadOne()
    {
        self::$cache->addResource($resource = __DIR__.'/../Fixtures/test_service1.yml');
        $loader = $this->util->createInterfaceMock('Symfony\Component\Config\Loader\LoaderInterface');
        $loader
            ->expects($this->once())
            ->method('load')
            ->with($resource)
            ->willReturn($expected = array('aaa' => 'bbb'))
            ;
        self::$cache->setLoader($loader);
        $this->assertSame($expected, $this->util->invoke(self::$cache, 'loadOne'));
    }

    /**
     * assert only validated
     *
     * @dataProvider processConfigurationProvider
     */
    public function testProcessConfiguration($validated, $validating, $expected)
    {
        $configuration  = new ConfigCacheConfiguration();
        if (is_string($expected) && class_exists($expected)) {
            $this->setExpectedException($expected);
        }
        list($array, $node) = $this->util->invoke(
            self::$cache,
            'processConfiguration',
            $validated,
            $validating,
            $configuration,
            $configuration->getConfigTreeBuilder()->buildTree()
        );

        $this->assertSame($expected, $array);
        $this->assertEquals($node, $configuration->getConfigTreeBuilder()->buildTree());
    }

    /**
     * @return array ($validated, $validating, $expected)
     */
    public function processConfigurationProvider()
    {
        return array(
            // normal
            array(
                array('zzz' => 'www', 'xxx' => 'yyy'),
                array('test_service' => array('aaa' => 'bbb', 'ccc' => 'ddd')),
                array('zzz' => 'www', 'xxx' => 'yyy', 'aaa' => 'bbb', 'ccc' => 'ddd'),
            ),
            // invalid node
            array(
                array('zzz' => 'www', 'xxx' => 'yyy'),
                array('test_service' => array('aaa' => 'bbb', 'ccc' => 'ddd', 'eee' => 'fff')),
                'Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
            ),
            // invalid root node
            array(
                array('zzz' => 'www', 'xxx' => 'yyy'),
                array('invalid_service' => array('aaa' => 'bbb', 'ccc' => 'ddd')),
                'Exception',
            ),
        );
    }

    /**
     * @dataProvider createMasterNodeProvider
     */
    public function testCreateMasterNode($configuration, $expected)
    {
        // use reflection to set null
        $actual = $this->util
            ->setProperty(self::$cache, 'configuration', $configuration)
            ->invoke(self::$cache, 'createMasterNode')
            ;
        if ($expected instanceof NodeInterface) {
            $this->assertEquals($expected, $actual);
        } else {
            $this->assertSame($expected, $actual);
        }
    }

    /**
     * @return array ($configuration, $expected)
     */
    public function createMasterNodeProvider()
    {
        $configuration = new ConfigCacheConfiguration();

        return array(
            // disabled configuration setting
            array(
                null,
                null,
            ),
            // enabled configuration setting
            array(
                $configuration,
                $configuration->getConfigTreeBuilder()->buildTree(),
            ),
        );
    }

    public function testSetId()
    {
        // OK
        self::$cache->setId($expected = 'test');
        $this->assertSame($expected, $this->util->getProperty(self::$cache, 'id'));
        // exception
        $this->setExpectedException('\RuntimeException');
        self::$cache->setId('zzz');
    }

    /**
     * test setStrict(), isStrict()
     */
    public function testStrict()
    {
        $this->assertTrue($this->util->invoke(self::$cache, 'isStrict'));
        self::$cache->setStrict(false);
        $this->assertFalse($this->util->invoke(self::$cache, 'isStrict'));
    }

    protected function createConfigCacheMock(array $methods = null)
    {
        return $this->util->createMock('YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache', $methods);
    }
}
