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

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;
use YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache;
use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\DirectoryResource;
use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\FileResource;
use YahooJapan\ConfigCacheBundle\ConfigCache\RestorablePhpFileCache;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\RegisterConfiguration;

class RegisterTest extends RegisterTestCase
{
    // post-processing here to operate file on testRegisterInternal, testFindFilesByDirectory
    protected function tearDown()
    {
        if (file_exists($this->getTmpDir())) {
            $file = new Filesystem();
            $file->remove($this->getTmpDir());
        }
    }

    protected function getTmpDir()
    {
        return sys_get_temp_dir().'/yahoo_japan_config_cache';
    }

    public function testConstruct()
    {
        $extension = $this->createExtensionMock();
        $container = $this->getContainerBuilder();
        $className = 'YahooJapan\ConfigCacheBundle\ConfigCache\Register';
        $register  = $this->util->createMock($className, array('initialize'));
        $register
            ->expects($this->exactly(2))
            ->method('initialize')
            ->willReturn(null)
            ;
        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();
        $this->assertNull($constructor->invoke($register, $extension, $container, array()));
        $this->assertNull($constructor->invoke($register, $extension, $container, array(), array()));
    }

    /**
     * assert only calling registerInternal() \n
     * internal processing is particularly asserted each test methods
     */
    public function testRegister()
    {
        $register = $this->createRegisterMock(array('initializeResources', 'registerInternal'));
        $register
            ->expects($this->once())
            ->method('initializeResources')
            ->willReturnSelf()
            ;
        $register
            ->expects($this->once())
            ->method('registerInternal')
            ->willReturn(null)
            ;
        $register->register();
    }

    /**
     * assert only calling registerInternal() \n
     * internal processing is particularly asserted each test methods
     */
    public function testRegisterAll()
    {
        $register = $this->createRegisterMock(array('initializeAllResources', 'registerInternal'));
        $this->util->setProperty($register, 'container', $this->getContainerBuilder());
        $register
            ->expects($this->once())
            ->method('initializeAllResources')
            ->willReturnSelf()
            ;
        $register
            ->expects($this->once())
            ->method('registerInternal')
            ->willReturn(null)
            ;
        $register->registerAll();
    }

    public function testSetAppConfig()
    {
        $register        = $this->createRegisterMock();
        $serviceRegister = $this->util->getProperty($register, 'serviceRegister');
        $config          = array('aaa' => 'bbb');
        $register->setAppConfig($config);
        $this->assertSame($config, $this->util->getProperty($serviceRegister, 'appConfig'));
    }

    public function testSetTag()
    {
        $register        = $this->createRegisterMock();
        $serviceRegister = $this->util->getProperty($register, 'serviceRegister');
        $tag             = 'test_tag';
        $register->setTag($tag);
        $this->assertSame($tag, $this->util->getProperty($serviceRegister, 'tag'));
    }

    /**
     * assert only calling registerInternal() \n
     * internal processing is particularly asserted each test methods
     */
    public function testInitialize()
    {
        $internalMethods = array(
            'setBundleId',
            'setConfigurationByExtension',
            'validateResources',
            'validateCacheId',
        );
        $register = $this->createRegisterMock($internalMethods);
        foreach ($internalMethods as $method) {
            $register
                ->expects($this->once())
                ->method($method)
                ->willReturn(null)
                ;
        }
        $this->util->invoke($register, 'initialize');
    }

    /**
     * register, registerAll, registerInternal test cases.
     *
     * @dataProvider registerInternalProvider
     */
    public function testRegisterAndRegisterAll(
        $all,
        $resources,
        $createFiles,
        $expCacheDefinition,
        $expConfigDefinition,
        $expConfigIds,
        $expAddMethodCalls
    ) {
        list($register, $container) = $this->createRegisterMockAndContainer();
        $id = 'register_test';

        // create directory/file
        $file = new Filesystem();
        if (!$file->exists($this->getTmpDir())) {
            $file->mkdir($this->getTmpDir());
        }
        if ($createFiles > 0) {
            foreach (range(1, $createFiles) as $i) {
                $file->touch($this->getTmpDir()."/testRegisterInternal{$i}");
            }
        }

        // initialization
        $register->setConfiguration(new RegisterConfiguration());
        $this->util->getProperty($register, 'idBuilder')->setBundleId($id);
        // not assert excluding setting test here for asserting on testFindFilesByDirectory
        $this->util->getProperty($register, 'directory')->setExcludes(array());
        $this->util->setProperty($register, 'resources', $resources);

        // store Definition count before registerInternal
        $definitions = count($container->getDefinitions());

        // registerInternal
        $this->util->invoke($register, $all ? 'registerAll' : 'register');

        // whether user cache service defined
        $this->assertSame(
            $expCacheDefinition,
            $container->hasDefinition("{$this->getCacheId()}.{$id}")
        );

        // whether configuration cache service defined
        // compare Definition count previous or next registerInternal
        // when passing setCacheDefinition(), incresing two (doctrine/cache, user cache)
        $adjustment = $expCacheDefinition ? 2 : 0;
        $this->assertSame(
            $expConfigDefinition,
            count($container->getDefinitions()) > $definitions + $adjustment
        );
        foreach ($expConfigIds as $configId) {
            $this->assertTrue($container->hasDefinition($configId));
        }

        // whether addMethodCall defined
        if ($container->hasDefinition("{$this->getCacheId()}.{$id}")) {
            // Definition
            $definition  = $container->getDefinition("{$this->getCacheId()}.{$id}");
            // DefinitionDecorator
            $parent      = $container->getDefinition($definition->getParent());
            $actualCalls = array_merge($parent->getMethodCalls(), $definition->getMethodCalls());
            foreach ($actualCalls as $index => $call) {
                if (isset($expAddMethodCalls[$index]) && is_array($expAddMethodCalls[$index])) {
                    foreach ($expAddMethodCalls[$index] as $method => $arguments) {
                        // method name
                        $this->assertSame($method, $call[0]);
                        // addResource
                        if (isset($arguments[0]) && isset($arguments[1]) && isset($arguments[2])) {
                            // file name
                            $this->assertSame($arguments[0], $call[1][0]);
                            // Reference instance
                            $this->assertInstanceOf($arguments[1], $call[1][1], 'Unexpected instance.');
                            // Configuration cache ID
                            $this->assertSame($arguments[2], (string) $call[1][1]);

                        // setConfiguration or setArrayAccess
                        } elseif (isset($arguments[0]) && isset($arguments[1])) {
                            // Reference instance
                            $this->assertInstanceOf($arguments[0], $call[1][0], 'Unexpected instance.');
                            // Configuration cache ID or ArrayAccess ID
                            $this->assertSame($arguments[1], (string) $call[1][0]);

                        // others are failed
                        } else {
                            $this->fail('ExpectedAddMethodCalls arguments include not set.');
                        }
                    }
                } else {
                    $this->fail('ExpectedAddMethodCalls first index is not set.');
                }
            }
        }
    }

    /**
     * @return
     *     array(
     *         $all,
     *         $resources,
     *         $createFiles,
     *         $expectedCacheDefinition,
     *         $expectedConfigDefinition,
     *         $expectedConfigIds,
     *         $expectedAddMethodCalls
     *     )
     */
    public function registerInternalProvider()
    {
        $id            = 'register_test';
        $configuration = new RegisterConfiguration();

        // finally discarded because of private service
        // {$this->getCacheId()}.configuration + Configuration ID (yahoo_japan.config_cache_bundle.config_cache.tests.fixtures.register_configuration)
        $configId = "{$this->getCacheId()}.configuration.yahoo_japan.config_cache_bundle.tests.fixtures.register_configuration";

        $frameworkBundle   = new \ReflectionClass('Symfony\Bundle\FrameworkBundle\FrameworkBundle');
        $configCacheBundle = new \ReflectionClass('YahooJapan\ConfigCacheBundle\YahooJapanConfigCacheBundle');

        return array(
            // specify a bundle (FileResource)
            array(
                null,
                array(
                    new FileResource(__DIR__.'/../Fixtures/test_service1.yml', $configuration),
                    new FileResource(__DIR__.'/../Fixtures/test_service2.yml', $configuration),
                ),
                0,
                true,
                true,
                array($configId),
                array(
                    array(
                        'setArrayAccess' => array(
                            'Symfony\Component\DependencyInjection\Reference',
                            'yahoo_japan_config_cache.array_access',
                        ),
                    ),
                    array(
                        'setConfiguration' => array(
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                    array(
                        'addResource' => array(
                            __DIR__.'/../Fixtures/test_service1.yml',
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                    array(
                        'addResource' => array(
                            __DIR__.'/../Fixtures/test_service2.yml',
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                ),
            ),
            // step over bundles (FileResource)
            array(
                true,
                array(new FileResource('/DependencyInjection/Configuration.php', $configuration)),
                0,
                true,
                true,
                array($configId),
                array(
                    array(
                        'setArrayAccess' => array(
                            'Symfony\Component\DependencyInjection\Reference',
                            'yahoo_japan_config_cache.array_access',
                        ),
                    ),
                    array(
                        'setConfiguration' => array(
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                    array(
                        'addResource' => array(
                            dirname($frameworkBundle->getFilename()).'/DependencyInjection/Configuration.php',
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                    array(
                        'addResource' => array(
                            dirname($configCacheBundle->getFilename()).'/DependencyInjection/Configuration.php',
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                ),
            ),
            // no Definition
            array(
                true,
                array(),
                0,
                false,
                false,
                array(),
                array(),
            ),
            // DirectoryResource
            array(
                null,
                array(new DirectoryResource($this->getTmpDir(), $configuration)),
                2,
                true,
                true,
                array($configId),
                array(
                    array(
                        'setArrayAccess' => array(
                            'Symfony\Component\DependencyInjection\Reference',
                            'yahoo_japan_config_cache.array_access',
                        ),
                    ),
                    array(
                        'setConfiguration' => array(
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                    array(
                        'addResource' => array(
                            $this->getTmpDir().'/testRegisterInternal1',
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                    array(
                        'addResource' => array(
                            $this->getTmpDir().'/testRegisterInternal2',
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                ),
            ),
            // mixed FileResource, DirectoryResource
            array(
                null,
                array(
                    new FileResource(__DIR__.'/../Fixtures/test_service1.yml', $configuration),
                    new DirectoryResource($this->getTmpDir(), $configuration),
                ),
                3,
                true,
                true,
                array($configId),
                array(
                    array(
                        'setArrayAccess' => array(
                            'Symfony\Component\DependencyInjection\Reference',
                            'yahoo_japan_config_cache.array_access',
                        ),
                    ),
                    array(
                        'setConfiguration' => array(
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                    array(
                        'addResource' => array(
                            __DIR__.'/../Fixtures/test_service1.yml',
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                    array(
                        'addResource' => array(
                            $this->getTmpDir().'/testRegisterInternal1',
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                    array(
                        'addResource' => array(
                            $this->getTmpDir().'/testRegisterInternal2',
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                    array(
                        'addResource' => array(
                            $this->getTmpDir().'/testRegisterInternal3',
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * Focus FileResource with alias test case.
     *
     * @dataProvider registerInternalWithAliasProvider
     */
    public function testRegisterInternalWithAlias(
        $resources,
        $createFiles,
        array $expected,
        $expectedException,
        $restorable
    ) {
        list($register, $container) = $this->createRegisterMockAndContainer();
        $id = 'register_test';

        // create directory/file
        $file = new Filesystem();
        if (!$file->exists($this->getTmpDir())) {
            $file->mkdir($this->getTmpDir());
        }
        if ($createFiles > 0) {
            foreach (range(1, $createFiles) as $i) {
                $file->touch($this->getTmpDir()."/testRegisterInternal{$i}");
            }
        }

        // initialization
        $register->setConfiguration(new RegisterConfiguration());
        $this->util->getProperty($register, 'idBuilder')->setBundleId($id);
        // not assert excluding setting test here for asserting on testFindFilesByDirectory
        $this->util->getProperty($register, 'directory')->setExcludes(array());
        $this->util->setProperty($register, 'resources', $resources);

        // store Definition count before register
        $definitions = count($container->getDefinitions());

        if (!is_null($expectedException)) {
            $this->setExpectedException($expectedException);
        }

        // register
        $this->util->invoke($register, 'register');

        // whether user cache service defined
        foreach ($expected as $serviceId => $expectedCalls) {
            $this->assertTrue($container->hasDefinition($serviceId));
            // Definition
            $definition  = $container->getDefinition($serviceId);
            // DefinitionDecorator
            $parent      = $container->getDefinition($definition->getParent());
            $actualCalls = array_merge($parent->getMethodCalls(), $definition->getMethodCalls());
            foreach ($actualCalls as $i => $call) {
                // method name
                $this->assertSame(key($expectedCalls[$i]), $call[0]);
                // arguments
                if (isset($call[1]) && is_array($call[1])) {
                    foreach ($call[1] as $j => $argument) {
                        $actual = $argument instanceof Reference ? (string) $argument : $argument;
                        $this->assertSame($expectedCalls[$i][$call[0]][$j], $actual);
                    }
                }
            }

            $this->assertTrue($definition->hasTag(ConfigCache::TAG_CACHE_WARMER));

            if ($restorable) {
                // first argument of ConfigCache will be RestorablePhpFileCache
                $phpFileCacheId = (string) $container->getDefinition($serviceId)->getArgument(0);
                $parent = $container->getDefinition($phpFileCacheId)->getParent();
                $this->assertSame('yahoo_japan_config_cache.restorable_php_file_cache', $parent);
                $this->assertTrue($definition->hasTag(RestorablePhpFileCache::TAG_RESTORABLE_CACHE));
            }
        }
    }

    /**
     * @return array($resources, $createFiles, $expected, $expectedException, $restorable)
     */
    public function registerInternalWithAliasProvider()
    {
        $bundleId        = 'register_test';
        $cacheId         = $this->getCacheId();
        $baseId          = "{$cacheId}.{$bundleId}";
        $arrayAccessId   = 'yahoo_japan_config_cache.array_access';
        $configurationId = "{$cacheId}.configuration.yahoo_japan.config_cache_bundle.tests.fixtures.register_configuration";

        return array(
            // FileResource with alias
            array(
                array(
                    new FileResource(__DIR__.'/../Fixtures/test_service1.yml', null, $alias1 = 'test_alias1'),
                    new FileResource(__DIR__.'/../Fixtures/test_service2.yml', null, $alias2 = 'test_alias2'),
                ),
                0,
                array(
                    // serviceId => calls
                    "{$baseId}.{$alias1}" => array(
                        // methodName => arguments
                        array('setArrayAccess' => array($arrayAccessId)),
                        array('addResource'    => array(__DIR__.'/../Fixtures/test_service1.yml')),
                        array('setStrict'      => array(false)),
                        array('setId'          => array($alias1)),
                    ),
                    "{$baseId}.{$alias2}" => array(
                        array('setArrayAccess' => array($arrayAccessId)),
                        array('addResource'    => array(__DIR__.'/../Fixtures/test_service2.yml')),
                        array('setStrict'      => array(false)),
                        array('setId'          => array($alias2)),
                    ),
                ),
                null,
                false,
            ),
            // mixed FilesResource, FileResource with alias
            array(
                array(
                    new FileResource(__DIR__.'/../Fixtures/test_service1.yml', null, $alias1 = 'test_alias1'),
                    new FileResource(__DIR__.'/../Fixtures/test_service1.yml', new RegisterConfiguration()),
                    new FileResource(__DIR__.'/../Fixtures/test_service2.yml', null, $alias2 = 'test_alias2'),
                ),
                0,
                array(
                    // serviceId => calls (any order)
                    "{$baseId}.{$alias1}" => array(
                        // methodName => arguments
                        array('setArrayAccess' => array($arrayAccessId)),
                        array('addResource'    => array(__DIR__.'/../Fixtures/test_service1.yml')),
                        array('setStrict'      => array(false)),
                        array('setId'          => array($alias1)),
                    ),
                    "{$baseId}.{$alias2}" => array(
                        array('setArrayAccess' => array($arrayAccessId)),
                        array('addResource'    => array(__DIR__.'/../Fixtures/test_service2.yml')),
                        array('setStrict'      => array(false)),
                        array('setId'          => array($alias2)),
                    ),
                    $baseId => array(
                        array('setArrayAccess'   => array($arrayAccessId)),
                        array('setConfiguration' => array($configurationId)),
                        array('addResource'      => array(
                            __DIR__.'/../Fixtures/test_service1.yml',
                            $configurationId,
                        )),
                    ),
                ),
                null,
                false,
            ),
            // duplicated aliases
            array(
                array(
                    new FileResource(__DIR__.'/../Fixtures/test_service1.yml', null, $alias = 'duplicated_alias'),
                    new FileResource(__DIR__.'/../Fixtures/test_service2.yml', null, $alias),
                ),
                0,
                array(),
                '\RuntimeException',
                false,
            ),
            // FileResource with alias and restorable
            array(
                array(
                    new FileResource(__DIR__.'/../Fixtures/test_service1.yml', null, $alias1 = 'test_alias1', true),
                ),
                0,
                array(
                    // serviceId => calls
                    "{$baseId}.{$alias1}" => array(
                        // methodName => arguments
                        array('setArrayAccess' => array($arrayAccessId)),
                        array('addResource'    => array(__DIR__.'/../Fixtures/test_service1.yml')),
                        array('setStrict'      => array(false)),
                        array('setId'          => array($alias1)),
                    ),
                ),
                null,
                true,
            ),
        );
    }

    /**
     * @dataProvider initializeResourcesProvider
     */
    public function testInitializeResources($resources, $expectedDirs, $expectedFiles, $expectedMethodCalls)
    {
        list($register, ) = $this->createRegisterMockAndContainer();
        $id = 'register_test';
        // set a mock to assert method calls
        $serviceRegister = $this->createServiceRegisterMock(array('registerConfigCache'));
        $this->util->setProperty($register, 'serviceRegister', $serviceRegister);

        // only assert calling method
        $serviceRegister
            ->expects($this->exactly($expectedMethodCalls))
            ->method('registerConfigCache')
            ->willReturn(null)
            ;
        $this->util->getProperty($register, 'idBuilder')->setBundleId($id);
        $this->util
            ->setProperty($register, 'resources', $resources)
            ->invoke($register, 'initializeResources')
            ;

        $this->assertSame($expectedDirs, $this->util->getProperty($this->util->getProperty($register, 'directory'), 'resources'));
        $this->assertSame($expectedFiles, $this->util->getProperty($this->util->getProperty($register, 'file'), 'resources'));
    }

    /**
     * @return array ($resources, $expectedDirs, $expectedFiles, $expectedMethodCalls)
     */
    public function initializeResourcesProvider()
    {
        $configuration        = new RegisterConfiguration();
        $fileResource         = new FileResource(__DIR__.'/../Fixtures/test_service1.yml', $configuration);
        $fileResource2        = new FileResource(__DIR__.'/../Fixtures/test_service2.yml', $configuration);
        $directoryResource    = new DirectoryResource(__DIR__.'/../Fixtures', $configuration);
        $noExistsFileResource = new FileResource(__DIR__.'/../Fixtures/noExists.yml', $configuration);
        $withAlias            = new FileResource(__DIR__.'/../Fixtures/test_service1.yml', null, 'test_alias');

        return array(
            // FileResource
            array(
                array($fileResource),
                array(),
                array($fileResource),
                1,
            ),
            // FileResource with alias
            array(
                array($withAlias),
                array(),
                array($withAlias),
                0,
            ),
            // DirectoryResource
            array(
                array($directoryResource),
                array($directoryResource),
                array(),
                1,
            ),
            // resources zero
            array(
                array(),
                array(),
                array(),
                0,
            ),
            // resources one (no file)
            array(
                array($noExistsFileResource),
                array(),
                array(),
                0,
            ),
            // resources greater than two (file exists)
            array(
                array($fileResource, $fileResource2),
                array(),
                array($fileResource, $fileResource2),
                1,
            ),
            // resources greater than two (no file)
            array(
                array($noExistsFileResource, $noExistsFileResource),
                array(),
                array(),
                0,
            ),
        );
    }

    /**
     * @dataProvider initializeAllResourcesProvider
     */
    public function testInitializeAllResources($bundles, $resources, $expectedDirs, $expectedFiles, $expectedMethodCalls)
    {
        list($register, ) = $this->createRegisterMockAndContainer();
        // set a mock to assert method calls
        $serviceRegister = $this->createServiceRegisterMock(array('registerConfigCache'));
        $this->util->setProperty($register, 'serviceRegister', $serviceRegister);

        // only assert calling method
        $serviceRegister
            ->expects($this->exactly($expectedMethodCalls))
            ->method('registerConfigCache')
            ->willReturn(null)
            ;
        // $register->initializeAllResources()
        $this->util
            ->setProperty($register, 'resources', $resources)
            ->invoke($register, 'initializeAllResources', $bundles)
            ;
        $dirs  = $this->util->getProperty($this->util->getProperty($register, 'directory'), 'resources');
        $files = $this->util->getProperty($this->util->getProperty($register, 'file'), 'resources');

        // regard OK as asserting according with count(), getResource(), getConfiguration()
        $this->assertSame(count($expectedDirs), count($dirs));
        $this->assertSame(count($expectedFiles), count($files));
        foreach ($dirs as $index => $dir) {
            $class = new \ReflectionClass($expectedDirs[$index]);
            $this->assertInstanceOf($class->getName(), $dir, 'Unexpected instance matches');
            $this->assertSame($expectedDirs[$index]->getResource(), $dir->getResource());
            $this->assertSame($expectedDirs[$index]->getConfiguration(), $dir->getConfiguration());
        }
        foreach ($files as $index => $file) {
            $class = new \ReflectionClass($expectedFiles[$index]);
            $this->assertInstanceOf($class->getName(), $file, 'Unexpected instance matches');
            $this->assertSame($expectedFiles[$index]->getResource(), $file->getResource());
            $this->assertSame($expectedFiles[$index]->getConfiguration(), $file->getConfiguration());
        }
    }

    /**
     * @return array ($bundles, $resources, $expectedDirs, $expectedFiles, $expectedMethodCalls)
     */
    public function initializeAllResourcesProvider()
    {
        $bundles = array(
            'FrameworkBundle'             => 'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle',
            'YahooJapanConfigCacheBundle' => 'YahooJapan\\ConfigCacheBundle\\YahooJapanConfigCacheBundle',
        );
        $configuration     = new RegisterConfiguration();
        $frameworkBundle   = new \ReflectionClass('Symfony\Bundle\FrameworkBundle\FrameworkBundle');
        $configCacheBundle = new \ReflectionClass('YahooJapan\ConfigCacheBundle\YahooJapanConfigCacheBundle');

        return array(
            // FileResource
            array(
                $bundles,
                array(new FileResource('/Resources/config/web.xml', $configuration)),
                array(),
                array(new FileResource(
                    dirname($frameworkBundle->getFilename()).'/Resources/config/web.xml',
                    $configuration
                )),
                1,
            ),
            // only FileResource with alias
            array(
                $bundles,
                array($resource = new FileResource(__DIR__.'/../Fixtures/test_service1.yml', null, 'test_alias')),
                array(),
                array($resource),
                0,
            ),
            // DirectoryResource
            // resources zero
            array(
                $bundles,
                array(),
                array(),
                array(),
                0,
            ),
            // resources one (no file)
            array(
                $bundles,
                array(new FileResource('/Resources/config/no_exists.yml', $configuration)),
                array(),
                array(),
                0,
            ),
            // resources one (no directory)
            array(
                $bundles,
                array(new DirectoryResource('/Resources/config/no_exists', $configuration)),
                array(),
                array(),
                0,
            ),
            // resources greater than two (file exists)
            array(
                $bundles,
                array(new FileResource('/DependencyInjection/Configuration.php', $configuration)),
                array(),
                array(
                    new FileResource(
                        dirname($frameworkBundle->getFilename()).'/DependencyInjection/Configuration.php',
                        $configuration
                    ),
                    new FileResource(
                        dirname($configCacheBundle->getFilename()).'/DependencyInjection/Configuration.php',
                        $configuration
                    ),
                ),
                1,
            ),
            // resources greater than two (directory exists)
            array(
                $bundles,
                array(new DirectoryResource('/Resources/config', $configuration)),
                array(
                    new DirectoryResource(
                        dirname($frameworkBundle->getFilename()).'/Resources/config',
                        $configuration
                    ),
                    new DirectoryResource(
                        dirname($configCacheBundle->getFilename()).'/Resources/config',
                        $configuration
                    ),
                ),
                array(),
                1,
            ),
        );
    }

    /**
     * @dataProvider postInitializeResourcesProvider
     */
    public function testPostInitializeResources(array $files, array $dirs, $expectedMethodCalls)
    {
        $register = $this->createRegisterMock();
        // set a mock to assert method calls
        $serviceRegister = $this->createServiceRegisterMock(array('registerConfigCache'));
        $this->util->setProperty($register, 'serviceRegister', $serviceRegister);
        $serviceRegister
            ->expects($expectedMethodCalls ? $this->once() : $this->never())
            ->method('registerConfigCache')
            ->willReturn(null)
            ;
        foreach ($files as $file) {
            $this->util->getProperty($register, 'file')->add($file);
        }
        foreach ($dirs as $dir) {
            $this->util->getProperty($register, 'directory')->add($dir);
        }
        $this->util->invoke($register, 'postInitializeResources');
    }

    /**
     * @return array($files, $dirs, $expectedMethodCalls)
     */
    public function postInitializeResourcesProvider()
    {
        return array(
            // FileResource without alias
            array(
                array(new FileResource(__DIR__.'/../Fixtures/test_service1.yml')),
                array(),
                true,
            ),
            // DirectoryResource
            array(
                array(),
                array(new DirectoryResource($this->getTmpDir())),
                true,
            ),
            // FileResource with alias
            array(
                array(new FileResource(__DIR__.'/../Fixtures/test_service1.yml', null, 'test_alias')),
                array(),
                false,
            ),
        );
    }

    public function testSetBundleId()
    {
        $register  = $this->createRegisterMock();
        $extension = $this->createExtensionMock();
        $extension
            ->expects($this->once())
            ->method('getAlias')
            ->willReturn('register_test')
            ;
        $this->util
            ->setProperty($register, 'extension', $extension)
            ->invoke($register, 'setBundleId')
            ;
        $this->assertSame('register_test', $this->util->getProperty($register, 'idBuilder')->getBundleId());
    }

    /**
     * @dataProvider setConfigurationByExtensionProvider
     */
    public function testSetConfigurationByExtension($configuration, $expected)
    {
        list($register, ) = $this->createRegisterMockAndContainer();
        // mock of ConfigurationExtensionInterface in order to getConfiguration only
        $name      = 'Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface';
        $extension = $this->util->createInterfaceMock($name);
        $extension
            ->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configuration)
            ;
        $this->util
            ->setProperty($register, 'extension', $extension)
            ->invoke($register, 'setConfigurationByExtension')
            ;
        $this->assertSame(
            $expected,
            $this->util->getProperty($this->util->getProperty($register, 'configuration'), 'configuration')
        );
    }

    /**
     * @return array ($configuration, $expected)
     */
    public function setConfigurationByExtensionProvider()
    {
        $configuration = new RegisterConfiguration();
        return array(
            // getConfiguration()でconfigurationが取得できた
            // success to get configuration by getConfiguration()
            array($configuration, $configuration),
            // failed (=null)
            array(null, null),
        );
    }

    /**
     * @dataProvider validateResourcesProvider
     */
    public function testValidateResources($resources, $expected)
    {
        if (is_string($expected) && class_exists($expected)) {
            $this->setExpectedException($expected);
        }
        $register = $this->createRegisterMock();
        $this->util
            ->setProperty($register, 'resources', $resources)
            ->invoke($register, 'validateResources')
            ;
        $this->assertTrue($expected);
    }

    /**
     * @return array ($resources, $expected)
     */
    public function validateResourcesProvider()
    {
        return array(
            // normal
            array(
                array(
                    $this->createResourceMock(),
                    $this->createResourceMock(),
                ),
                true,
            ),
            // empty array
            array(
                array(),
                '\Exception',
            ),
            // exist resources except ResourceInterface
            array(
                array(
                    $this->createResourceMock(),
                    new \StdClass(),
                ),
                '\Exception',
            ),
        );
    }

    /**
     * @dataProvider validateCacheIdProvider
     */
    public function testValidateCacheId(array $bundles, $expected)
    {
        if (is_string($expected) && class_exists($expected)) {
            $this->setExpectedException($expected);
        }

        $className = 'Symfony\Component\DependencyInjection\ContainerBuilder';
        $container = $this->util->createMock($className, array('getParameter'));
        $container
            ->expects($this->once())
            ->method('getParameter')
            ->with('kernel.bundles')
            ->willReturn($bundles)
            ;
        $register = $this->createRegisterMock();
        $this->util
            ->setProperty($register, 'container', $container)
            ->invoke($register, 'validateCacheId')
            ;
        $this->assertTrue($expected);
    }

    /**
     * @return array ($bundles, $expected)
     */
    public function validateCacheIdProvider()
    {
        return array(
            // normal
            array(
                array(
                    'FrameworkBundle'             => 'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle',
                    'YahooJapanConfigCacheBundle' => 'YahooJapan\\ConfigCacheBundle\\YahooJapanConfigCacheBundle',
                ),
                true,
            ),
            // dupliated cacheId
            array(
                array(
                    'FrameworkBundle' => 'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle',
                    'ConfigBundle'    => 'YahooJapan\\ConfigBundle\\YahooJapanConfigBundle',
                ),
                '\Exception',
            ),
        );
    }

    protected function createExtensionMock()
    {
        return $this->util->createInterfaceMock('Symfony\Component\DependencyInjection\Extension\ExtensionInterface');
    }

    protected function createResourceMock()
    {
        $name = 'YahooJapan\ConfigCacheBundle\ConfigCache\Resource\ResourceInterface';

        return $this->findUtil()->createInterfaceMock($name);
    }
}
