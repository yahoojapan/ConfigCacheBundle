<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Filesystem\Filesystem;
use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\DirectoryResource;
use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\FileResource;
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
        $extension = $this->getMock('Symfony\Component\DependencyInjection\Extension\ExtensionInterface');
        $container = $this->getContainerBuilder();
        $className = 'YahooJapan\ConfigCacheBundle\ConfigCache\Register';
        $register  = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->setMethods(array('initialize'))
            ->getMock()
            ;
        $register
            ->expects($this->exactly(2))
            ->method('initialize')
            ->willReturn(null)
            ;
        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();
        $this->assertNull($constructor->invoke($register, $extension, array(), $container, array()));
        $this->assertNull($constructor->invoke($register, $extension, array(), $container, array(), array()));
    }

    /**
     * assert only calling registerInternal() \n
     * internal processing is particularly asserted each test methods
     */
    public function testRegister()
    {
        $internalMethod = 'registerInternal';
        $register = $this->getRegisterMock(array($internalMethod));
        $register
            ->expects($this->once())
            ->method($internalMethod)
            ->with(false)
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
        $internalMethod = 'registerInternal';
        $register = $this->getRegisterMock(array($internalMethod));
        $register
            ->expects($this->once())
            ->method($internalMethod)
            ->with(true)
            ->willReturn(null)
            ;
        $register->registerAll();
    }

    public function testSetTag()
    {
        $register = $this->getRegisterMock();
        $tag      = 'test_tag';
        $register->setTag($tag);
        $property = new \ReflectionProperty($register, 'tag');
        $property->setAccessible(true);
        $this->assertSame($tag, $property->getValue($register));
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
            'setParameter',
            'setLoaderDefinition',
        );
        $register = $this->getRegisterMock($internalMethods);
        foreach ($internalMethods as $method) {
            $register
                ->expects($this->once())
                ->method($method)
                ->willReturn(null)
                ;
        }
        $method = new \ReflectionMethod($register, 'initialize');
        $method->setAccessible(true);
        $method->invoke($register);
    }

    /**
     * @dataProvider registerInternalProvider
     */
    public function testRegisterInternal(
        $all,
        $resources,
        $createFiles,
        $expCacheDefinition,
        $expConfigDefinition,
        $expConfigIds,
        $expAddMethodCalls
    ) {
        list($register, $container) = $this->getRegisterMockAndContainerWithParameter();
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
        $bundleId = new \ReflectionProperty($register, 'bundleId');
        $bundleId->setAccessible(true);
        $bundleId->setValue($register, $id);
        $registerResources = new \ReflectionProperty($register, 'resources');
        $registerResources->setAccessible(true);
        $registerResources->setValue($register, $resources);
        $configuration = new \ReflectionProperty($register, 'configuration');
        $configuration->setAccessible(true);
        $configuration->setValue($register, new RegisterConfiguration());
        // not assert excluding setting test here for asserting on testFindFilesByDirectory
        $excludes = new \ReflectionProperty($register, 'excludes');
        $excludes->setAccessible(true);
        $excludes->setValue($register, array());
        // only setLoaderDefinition, setParameter() is excuted on getRegisterMockAndContainerWithParameter()
        $method = new \ReflectionMethod($register, 'setLoaderDefinition');
        $method->setAccessible(true);
        $method->invoke($register);

        // store Definition count before registerInternal
        $definitions = count($container->getValue($register)->getDefinitions());

        // registerInternal
        $method = new \ReflectionMethod($register, 'registerInternal');
        $method->setAccessible(true);
        is_null($all) ? $method->invoke($register) : $method->invoke($register, $all);

        // whether user cache service defined
        $this->assertSame(
            $expCacheDefinition,
            $container->getValue($register)->hasDefinition("{$this->getCacheId()}.{$id}")
        );

        // whether configuration cache service defined
        // compare Definition count previous or next registerInternal
        // when passing setCacheDefinition(), incresing two (doctrine/cache, user cache)
        $adjustment = $expCacheDefinition ? 2 : 0;
        $this->assertSame(
            $expConfigDefinition,
            count($container->getValue($register)->getDefinitions()) > $definitions + $adjustment
        );
        foreach ($expConfigIds as $configId) {
            $this->assertTrue($container->getValue($register)->hasDefinition($configId));
        }

        // whether addMethodCall defined
        if ($container->getValue($register)->hasDefinition("{$this->getCacheId()}.{$id}")) {
            $calls = $container->getValue($register)->getDefinition("{$this->getCacheId()}.{$id}")->getMethodCalls();
            foreach ($calls as $index => $call) {
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
                        'setConfiguration' => array(
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                    array(
                        'setArrayAccess' => array(
                            'Symfony\Component\DependencyInjection\Reference',
                            "{$this->getCacheId()}.array_access.{$id}",
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
                        'setConfiguration' => array(
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                    array(
                        'setArrayAccess' => array(
                            'Symfony\Component\DependencyInjection\Reference',
                            "{$this->getCacheId()}.array_access.{$id}",
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
                        'setConfiguration' => array(
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                    array(
                        'setArrayAccess' => array(
                            'Symfony\Component\DependencyInjection\Reference',
                            "{$this->getCacheId()}.array_access.{$id}",
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
                        'setConfiguration' => array(
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                    array(
                        'setArrayAccess' => array(
                            'Symfony\Component\DependencyInjection\Reference',
                            "{$this->getCacheId()}.array_access.{$id}",
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
                    array(
                        'addResource' => array(
                            __DIR__.'/../Fixtures/test_service1.yml',
                            'Symfony\Component\DependencyInjection\Reference',
                            $configId,
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider registerOneInternalProvider
     */
    public function testRegisterOneInternal($resources, $expectedDirs, $expectedFiles)
    {
        $internalMethod = 'setCacheDefinition';
        list($register, ) = $this->getRegisterMockAndContainerWithParameter(array($internalMethod));
        $id = 'register_test';

        $bundleId = new \ReflectionProperty($register, 'bundleId');
        $bundleId->setAccessible(true);
        $bundleId->setValue($register, $id);

        $registerResources = new \ReflectionProperty($register, 'resources');
        $registerResources->setAccessible(true);
        $registerResources->setValue($register, $resources);

        // only assert calling method
        $register
            ->expects($this->once())
            ->method($internalMethod)
            ->willReturn(null)
            ;

        $method = new \ReflectionMethod($register, 'registerOneInternal');
        $method->setAccessible(true);
        list($dirs, $files) = $method->invoke($register);
        $this->assertSame($expectedDirs, $dirs);
        $this->assertSame($expectedFiles, $files);
    }

    /**
     * @return array ($resources, $expectedDirs, $expectedFiles)
     */
    public function registerOneInternalProvider()
    {
        $configuration        = new RegisterConfiguration();
        $fileResource         = new FileResource(__DIR__.'/../Fixtures/test_service1.yml', $configuration);
        $fileResource2        = new FileResource(__DIR__.'/../Fixtures/test_service2.yml', $configuration);
        $directoryResource    = new DirectoryResource(__DIR__.'/../Fixtures', $configuration);
        $noExistsFileResource = new FileResource(__DIR__.'/../Fixtures/noExists.yml', $configuration);

        return array(
            // FileResource
            array(
                array($fileResource),
                array(),
                array($fileResource),
            ),
            // DirectoryResource
            array(
                array($directoryResource),
                array($directoryResource),
                array(),
            ),
            // resources zero
            array(
                array(),
                array(),
                array(),
            ),
            // resources one (no file)
            array(
                array($noExistsFileResource),
                array(),
                array(),
            ),
            // resources greater than two (file exists)
            array(
                array($fileResource, $fileResource2),
                array(),
                array($fileResource, $fileResource2),
            ),
            // resources greater than two (no file)
            array(
                array($noExistsFileResource, $noExistsFileResource),
                array(),
                array(),
            ),
        );
    }

    /**
     * $invokedCount is execution count of setCacheDefinition() internal calling \n
     * which $this->once() o $this->never()
     *
     * @dataProvider registerAllInternalProvider
     */
    public function testRegisterAllInternal($bundles, $resources, $invokedCount, $expectedDirs, $expectedFiles)
    {
        $internalMethod = 'setCacheDefinition';
        list($register, ) = $this->getRegisterMockAndContainerWithParameter(array($internalMethod));

        $registerResources = new \ReflectionProperty($register, 'resources');
        $registerResources->setAccessible(true);
        $registerResources->setValue($register, $resources);

        // only assert calling method
        $register
            ->expects($invokedCount)
            ->method($internalMethod)
            ->willReturn(null)
            ;

        // $register->registerAllInternal()
        $method = new \ReflectionMethod($register, 'registerAllInternal');
        $method->setAccessible(true);
        list($dirs, $files) = $method->invoke($register, $bundles);

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
     * @return array ($bundles, $resources, $invokedCount, $expectedDirs, $expectedFiles)
     */
    public function registerAllInternalProvider()
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
                $this->once(),
                array(),
                array(new FileResource(
                    dirname($frameworkBundle->getFilename()).'/Resources/config/web.xml',
                    $configuration
                )),
            ),
            // DirectoryResource
            // resources zero
            array(
                $bundles,
                array(),
                $this->never(),
                array(),
                array(),
            ),
            // resources one (no file)
            array(
                $bundles,
                array(new FileResource('/Resources/config/no_exists.yml', $configuration)),
                $this->never(),
                array(),
                array(),
            ),
            // resources one (no directory)
            array(
                $bundles,
                array(new DirectoryResource('/Resources/config/no_exists', $configuration)),
                $this->never(),
                array(),
                array(),
            ),
            // resources greater than two (file exists)
            array(
                $bundles,
                array(new FileResource('/DependencyInjection/Configuration.php', $configuration)),
                $this->once(),
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
            ),
            // resources greater than two (directory exists)
            array(
                $bundles,
                array(new DirectoryResource('/Resources/config', $configuration)),
                $this->once(),
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
            ),
        );
    }

    /**
     * @dataProvider findFilesByDirectoryProvider
     */
    public function testFindFilesByDirectory($resource, $excludes, $files, $expected)
    {
        // create directory/file
        $file = new Filesystem();
        if (!$file->exists($this->getTmpDir())) {
            $file->mkdir($this->getTmpDir());
        }
        if ($files > 0) {
            foreach (range(1, $files) as $i) {
                $file->touch($this->getTmpDir()."/testFindFilesByDirectory{$i}");
            }
        }

        $register = $this->getRegisterMock();
        $method = new \ReflectionMethod($register, 'findFilesByDirectory');
        $method->setAccessible(true);

        try {
            $finder = $method->invoke($register, $resource, $excludes);
        } catch (\Exception $e) {
            $this->assertInstanceOf($expected, $e, 'Unexpected exception occurred.');
            return;
        }

        $results = array();
        foreach ($finder as $file) {
            $results[] = (string) $file;
        }
        sort($results); // sort by file name
        $this->assertSame($expected, $results);
    }

    /**
     * @return array ($resource, $excludes, $files, $expected)
     */
    public function findFilesByDirectoryProvider()
    {
        $configuration = new RegisterConfiguration();
        return array(
            // no file
            array(
                new DirectoryResource($this->getTmpDir(), $configuration),
                array(),
                0,
                array(),
            ),
            // no directory (generally not occurring for checking is_dir())
            array(
                new DirectoryResource(__DIR__.'/no_exists', $configuration),
                array(),
                0,
                '\InvalidArgumentException',
            ),
            // a file
            array(
                new DirectoryResource($this->getTmpDir(), $configuration),
                array(),
                1,
                array($this->getTmpDir()."/testFindFilesByDirectory1"),
            ),
            // greater than two file
            array(
                new DirectoryResource($this->getTmpDir(), $configuration),
                array(),
                2,
                array(
                    $this->getTmpDir()."/testFindFilesByDirectory1",
                    $this->getTmpDir()."/testFindFilesByDirectory2",
                ),
            ),
            // file exists, enable excluding
            array(
                new DirectoryResource($this->getTmpDir(), $configuration),
                array(
                    $this->getTmpDir()."/testFindFilesByDirectory3",
                    $this->getTmpDir()."/testFindFilesByDirectory4",
                ),
                5,
                array(
                    $this->getTmpDir()."/testFindFilesByDirectory1",
                    $this->getTmpDir()."/testFindFilesByDirectory2",
                    $this->getTmpDir()."/testFindFilesByDirectory5",
                ),
            ),
        );
    }

    public function testSetBundleId()
    {
        $register  = $this->getRegisterMock();
        $extension = $this->getMock('Symfony\Component\DependencyInjection\Extension\ExtensionInterface');
        $extension
            ->expects($this->once())
            ->method('getAlias')
            ->willReturn('register_test')
            ;

        // $register->extension = $extension
        $property = new \ReflectionProperty($register, 'extension');
        $property->setAccessible(true);
        $property->setValue($register, $extension);

        // $register->setBundleId()
        $method = new \ReflectionMethod($register, 'setBundleId');
        $method->setAccessible(true);
        $method->invoke($register);

        // 'register_test' === $register->bundleId ?
        $property = new \ReflectionProperty($register, 'bundleId');
        $property->setAccessible(true);
        $this->assertSame('register_test', $property->getValue($register));
    }

    /**
     * @dataProvider setConfigurationByExtensionProvider
     */
    public function testSetConfigurationByExtension($configuration, $expected)
    {
        list($register, ) = $this->getRegisterMockAndContainer();
        // mock of ConfigurationExtensionInterface in order to getConfiguration only
        $extension = $this->getMock('Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface');
        $extension
            ->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configuration)
            ;

        $property = new \ReflectionProperty($register, 'extension');
        $property->setAccessible(true);
        $property->setValue($register, $extension);

        $method = new \ReflectionMethod($register, 'setConfigurationByExtension');
        $method->setAccessible(true);
        $method->invoke($register);

        $property = new \ReflectionProperty($register, 'configuration');
        $property->setAccessible(true);
        $this->assertSame($expected, $property->getValue($register));
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

    public function testSetParameter()
    {
        list($register, $container) = $this->getRegisterMockAndContainerWithParameter();
        $expected = array(
            'Doctrine\Common\Cache\PhpFileCache'                             => 'php_file_cache.class',
            'YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache'           => 'config_cache.class',
            'YahooJapan\ConfigCacheBundle\ConfigCache\Loader\YamlFileLoader' => 'yaml_file_loader.class',
            'YahooJapan\ConfigCacheBundle\ConfigCache\Loader\XmlFileLoader'  => 'xml_file_loader.class',
            'Symfony\Component\Config\Loader\LoaderResolver'                 => 'loader_resolver.class',
            'Symfony\Component\Config\Loader\DelegatingLoader'               => 'delegating_loader.class',
        );
        foreach ($expected as $className => $serviceId) {
            $this->assertTrue($container->getValue($register)->hasParameter("{$this->getCacheId()}.{$serviceId}"));
            $this->assertSame(
                $className,
                $container->getValue($register)->getParameter("{$this->getCacheId()}.{$serviceId}")
            );
        }
    }

    /**
     * test setCacheDefinition and createCacheDefinition in this method
     *
     * @dataProvider setCacheDefinitionProvider
     */
    public function testSetCacheDefinition($tag)
    {
        list($register, $container) = $this->getRegisterMockAndContainerWithParameter();
        $id = 'register_test';

        // $register->bundleId = 'register_test'
        $bundleId = new \ReflectionProperty($register, 'bundleId');
        $bundleId->setAccessible(true);
        $bundleId->setValue($register, $id);

        // $register->configs = array('aaa' => 'bbb')
        // use when asserting ConfigCache
        $configs = new \ReflectionProperty($register, 'config');
        $configs->setAccessible(true);
        $configs->setValue($register, array('aaa' => 'bbb'));

        // $register->configuration = new Configuration()
        // use also
        $configuration = new RegisterConfiguration();
        $property = new \ReflectionProperty($register, 'configuration');
        $property->setAccessible(true);
        $property->setValue($register, $configuration);

        // $register->setTag()
        if (!is_null($tag)) {
            $register->setTag($tag);
        }

        // $register->setCacheDefinition()
        $method = new \ReflectionMethod($register, 'setCacheDefinition');
        $method->setAccessible(true);
        $method->invoke($register);

        // assert(doctrine/cache)
        $doctrineCacheId = "{$this->getCacheId()}.doctrine.cache.{$id}";
        $this->assertTrue($container->getValue($register)->hasDefinition($doctrineCacheId));
        $definition = $container->getValue($register)->getDefinition($doctrineCacheId);
        $this->assertFalse($definition->isPublic());
        $this->assertSame('Doctrine\Common\Cache\PhpFileCache', $definition->getClass());
        $this->assertSame(
            array(
                $container->getValue($register)->getParameter('kernel.cache_dir')."/{$id}",
                '.php',
            ),
            $definition->getArguments()
        );

        // assert(ConfigCache)
        $userCacheId = "{$this->getCacheId()}.{$id}";
        $this->assertTrue($container->getValue($register)->hasDefinition($userCacheId));
        $definition = $container->getValue($register)->getDefinition($userCacheId);
        $this->assertTrue($definition->isPublic());
        $this->assertTrue($definition->isLazy());
        $this->assertSame('YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache', $definition->getClass());
        $arguments = $definition->getArguments();
        $this->assertSame(3, count($arguments));
        foreach ($arguments as $i => $argument) {
            if ($i === 0) {
                $this->assertInstanceOf(
                    'Symfony\Component\DependencyInjection\Reference',
                    $argument,
                    'Unexpected argument "0" instance.'
                );
                $this->assertSame($doctrineCacheId, (string) $argument);
            } elseif ($i === 1) {
                $this->assertInstanceOf(
                    'Symfony\Component\DependencyInjection\Reference',
                    $argument,
                    'Unexpected argument "1" instance.'
                );
                $this->assertSame("{$this->getCacheId()}.delegating_loader", (string) $argument);
            } elseif ($i === 2) {
                $this->assertSame(array('aaa' => 'bbb'), $argument);
            } else {
                $this->fail(sprintf('The ConfigCache argument "%s" is not set.', $i));
            }
        }
        if (!is_null($tag)) {
            $this->assertTrue($definition->hasTag($tag));
        } else {
            $this->assertSame(array(), $definition->getTags());
        }
        // assert addMethodCalls simplified
        $calls = $definition->getMethodCalls();
        $this->assertSame('setConfiguration', $calls[0][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $calls[0][1][0]);
        $this->assertSame('setArrayAccess', $calls[1][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $calls[1][1][0]);

        // assert(Configuration)
        $method = new \ReflectionMethod($register, 'buildConfigurationId');
        $method->setAccessible(true);
        $configId = $method->invoke($register, $configuration);
        $this->assertTrue($container->getValue($register)->hasDefinition($configId));
        $definition = $container->getValue($register)->getDefinition($configId);
        $this->assertFalse($definition->isPublic());
        $this->assertSame('YahooJapan\ConfigCacheBundle\Tests\Fixtures\RegisterConfiguration', $definition->getClass());
        $this->assertSame(0, count($definition->getArguments()));

        // assert(ArrayAccess)
        $arrayAccessId = "{$this->getCacheId()}.array_access.{$id}";
        $this->assertTrue($container->getValue($register)->hasDefinition($arrayAccessId));
        $definition = $container->getValue($register)->getDefinition($arrayAccessId);
        $this->assertFalse($definition->isPublic());
        $this->assertSame('YahooJapan\ConfigCacheBundle\ConfigCache\Util\ArrayAccess', $definition->getClass());
        $this->assertSame(0, count($definition->getArguments()));
    }

    /**
     * @return array ($tag)
     */
    public function setCacheDefinitionProvider()
    {
        return array(
            // no tag
            array(null),
            // has tag
            array('test_tag'),
        );
    }

    public function testSetConfigurationDefinition()
    {
        list($register, $container) = $this->getRegisterMockAndContainer();
        $configuration = new RegisterConfiguration();
        $id            = 'register_test';

        $method = new \ReflectionMethod($register, 'setConfigurationDefinition');
        $method->setAccessible(true);

        // state not registered ID
        $method->invoke($register, $id, $configuration);
        $this->assertTrue($container->getValue($register)->hasDefinition($id));
        $definition = $container->getValue($register)->getDefinition($id);
        $this->assertFalse($definition->isPublic());
        $this->assertSame('YahooJapan\ConfigCacheBundle\Tests\Fixtures\RegisterConfiguration', $definition->getClass());

        // state already registered ID
        $mock = $this->getMock('Symfony\Component\Config\Definition\ConfigurationInterface');
        $method->invoke($register, $id, $mock);
        $definition = $container->getValue($register)->getDefinition($id);
        $this->assertSame('YahooJapan\ConfigCacheBundle\Tests\Fixtures\RegisterConfiguration', $definition->getClass());
        $this->assertFalse(strpos('Mock_ConfigurationInterface', $definition->getClass()) === 0);
    }

    public function testSetLoaderDefinition()
    {
        list($register, $container) = $this->getRegisterMockAndContainerWithParameter();
        $method = new \ReflectionMethod($register, 'setLoaderDefinition');
        $method->setAccessible(true);
        $method->invoke($register);

        // assert
        $yamlLoaderId       = "{$this->getCacheId()}.yaml_file_loader";
        $xmlLoaderId        = "{$this->getCacheId()}.xml_file_loader";
        $resolverId         = "{$this->getCacheId()}.loader_resolver";
        $delegatingLoaderId = "{$this->getCacheId()}.delegating_loader";

        $this->assertTrue($container->getValue($register)->hasDefinition($yamlLoaderId));
        $this->assertTrue($container->getValue($register)->hasDefinition($xmlLoaderId));
        $this->assertTrue($container->getValue($register)->hasDefinition($resolverId));
        $this->assertTrue($container->getValue($register)->hasDefinition($delegatingLoaderId));

        $yamlLoaderDefinition = $container->getValue($register)->getDefinition($yamlLoaderId);
        $xmlLoaderDefinition  = $container->getValue($register)->getDefinition($xmlLoaderId);
        $resolverDefinition   = $container->getValue($register)->getDefinition($resolverId);
        $delLoaderDefinition  = $container->getValue($register)->getDefinition($delegatingLoaderId);

        $this->assertFalse($yamlLoaderDefinition->isPublic());
        $this->assertFalse($xmlLoaderDefinition->isPublic());
        $this->assertFalse($resolverDefinition->isPublic());
        $this->assertFalse($delLoaderDefinition->isPublic());

        $this->assertSame('YahooJapan\ConfigCacheBundle\ConfigCache\Loader\YamlFileLoader', $yamlLoaderDefinition->getClass());
        $this->assertSame('YahooJapan\ConfigCacheBundle\ConfigCache\Loader\XmlFileLoader', $xmlLoaderDefinition->getClass());
        $this->assertSame('Symfony\Component\Config\Loader\LoaderResolver', $resolverDefinition->getClass());
        $this->assertSame('Symfony\Component\Config\Loader\DelegatingLoader', $delLoaderDefinition->getClass());

        $arguments = $resolverDefinition->getArguments();
        if (isset($arguments[0][0])) {
            $this->assertInstanceOf(
                'Symfony\Component\DependencyInjection\Reference',
                $arguments[0][0],
                'Unexpected argument "0-0" instance.'
            );
            $this->assertSame($yamlLoaderId, (string) $arguments[0][0]);
        } else {
            $this->fail('The LoaderResolver argument "0-0" is not set.');
        }
        if (isset($arguments[0][1])) {
            $this->assertInstanceOf(
                'Symfony\Component\DependencyInjection\Reference',
                $arguments[0][1],
                'Unexpected argument "0-1" instance.'
            );
            $this->assertSame($xmlLoaderId, (string) $arguments[0][1]);
        } else {
            $this->fail('The LoaderResolver argument "0-1" is not set.');
        }

        $arguments = $delLoaderDefinition->getArguments();
        if (isset($arguments[0])) {
            $this->assertInstanceOf(
                'Symfony\Component\DependencyInjection\Reference',
                $arguments[0],
                'Unexpected argument "0" instance.'
            );
            $this->assertSame($resolverId, (string) $arguments[0]);
        } else {
            $this->fail('The DelegatingLoader argument "0" is not set.');
        }
    }

    /**
     * @dataProvider      setLoaderDefinitionExceptionProvider
     * @expectedException \Exception
     */
    public function testSetLoaderDefinitionException($preDefinedId)
    {
        list($register, $container) = $this->getRegisterMockAndContainerWithParameter();
        $noUsedId = 'register_test';

        // set to be duplicated Definition
        $container->getValue($register)->setDefinition($preDefinedId, new Definition($noUsedId));

        // exception thrown
        $method = new \ReflectionMethod($register, 'setLoaderDefinition');
        $method->setAccessible(true);
        $method->invoke($register);

        // not pass here
        $this->fail('Expected exception does not occurred.');
    }

    /**
     * @return array ($preDefinedId)
     */
    public function setLoaderDefinitionExceptionProvider()
    {
        return array(
            array("{$this->getCacheId()}.yaml_file_loader"),
            array("{$this->getCacheId()}.xml_file_loader"),
            array("{$this->getCacheId()}.loader_resolver"),
            array("{$this->getCacheId()}.delegating_loader"),
        );
    }

    /**
     * @dataProvider validateResourcesProvider
     */
    public function testValidateResources($resources, $expected)
    {
        $register = $this->getRegisterMock();
        $property = new \ReflectionProperty($register, 'resources');
        $property->setAccessible(true);
        $property->setValue($register, $resources);
        $method = new \ReflectionMethod($register, 'validateResources');
        $method->setAccessible(true);

        try {
            $method->invoke($register);
        } catch (\Exception $e) {
            $this->assertInstanceOf($expected, $e, 'Unexpected exception occurred.');
            return;
        }
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
                    $this->getMock('YahooJapan\ConfigCacheBundle\ConfigCache\Resource\ResourceInterface'),
                    $this->getMock('YahooJapan\ConfigCacheBundle\ConfigCache\Resource\ResourceInterface'),
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
                    $this->getMock('YahooJapan\ConfigCacheBundle\ConfigCache\Resource\ResourceInterface'),
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
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock()
            ;
        $container
            ->expects($this->once())
            ->method('getParameter')
            ->with('kernel.bundles')
            ->willReturn($bundles)
            ;

        $register = $this->getRegisterMock();
        $property = new \ReflectionProperty($register, 'container');
        $property->setAccessible(true);
        $property->setValue($register, $container);
        $method = new \ReflectionMethod($register, 'validateCacheId');
        $method->setAccessible(true);

        try {
            $method->invoke($register);
        } catch (\Exception $e) {
            $this->assertInstanceOf($expected, $e, 'Unexpected exception occurred.');
            return;
        }
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

    /**
     * @dataProvider parseServiceIdProvider
     */
    public function testParseServiceId($name, $expected)
    {
        $register = $this->getRegisterMock();
        $method = new \ReflectionMethod($register, 'parseServiceId');
        $method->setAccessible(true);
        $this->assertSame($expected, $method->invoke($register, $name));
    }

    /**
     * @return array($name, $expected)
     */
    public function parseServiceIdProvider()
    {
        return array(
            // "Bundle" suffix
            array('YahooJapanConfigCacheBundle', 'yahoo_japan_config_cache'),
            // "Bundle" include (generally not occurring)
            array('YahooJapanConfigCacheBundleTest', 'yahoo_japan_config_cache_bundle_test'),
            // "Bundle" not include (generaty not occurring)
            array('YahooJapanConfigCacheTest', 'yahoo_japan_config_cache_test'),
        );
    }

    /**
     * @dataProvider buildIdProvider
     */
    public function testBuildId($suffix, $expected)
    {
        $register = $this->getRegisterMock();
        $method = new \ReflectionMethod($register, 'buildId');
        $method->setAccessible(true);
        $this->assertSame($expected, $method->invoke($register, $suffix));
    }

    /**
     * @return array ($suffix, $expected)
     */
    public function buildIdProvider()
    {
        return array(
            array('hoge', "{$this->getCacheId()}.hoge"),
            array(array('hoge', 'fuga'), "{$this->getCacheId()}.hoge.fuga"),
        );
    }

    /**
     * @dataProvider buildClassIdProvider
     */
    public function testBuildClassId($suffix, $expected)
    {
        $register = $this->getRegisterMock();
        $method = new \ReflectionMethod($register, 'buildClassId');
        $method->setAccessible(true);
        $this->assertSame($expected, $method->invoke($register, $suffix));
    }

    /**
     * @return array ($suffix, $expected)
     */
    public function buildClassIdProvider()
    {
        return array(
            array('hoge', "{$this->getCacheId()}.hoge.class"),
            array(array('hoge', 'fuga'), "{$this->getCacheId()}.hoge.fuga.class"),
        );
    }

    /**
     * @dataProvider buildConfigurationIdProvider
     */
    public function testBuildConfigurationId($configuration, $expected)
    {
        $register = $this->getRegisterMock();
        $method = new \ReflectionMethod($register, 'buildConfigurationId');
        $method->setAccessible(true);
        $this->assertSame($expected, $method->invoke($register, $configuration));
    }

    /**
     * @return array ($bundleId, $configuration, $expected)
     */
    public function buildConfigurationIdProvider()
    {
        return array(
            // Configuration but no "_" has
            array(
                new \YahooJapan\ConfigCacheBundle\Tests\Fixtures\Configuration(),
                "{$this->getCacheId()}.configuration.yahoo_japan.config_cache_bundle.tests.fixtures.configuration",
            ),
            // Configuration and "_" has
            array(
                new RegisterConfiguration(),
                "{$this->getCacheId()}.configuration.yahoo_japan.config_cache_bundle.tests.fixtures.register_configuration",
            ),
        );
    }

    public function testFindConfigurationByResource()
    {
        $mock = $this->getMock('Symfony\Component\Config\Definition\ConfigurationInterface');
        $real = new RegisterConfiguration();
        $register = $this->getRegisterMock();
        $register->setConfiguration($real);

        $reflection = new \ReflectionMethod($register, 'findConfigurationByResource');
        $reflection->setAccessible(true);

        // enabled configuration setting on resource
        $resource = new FileResource(__DIR__.'/../Fixtures/test_service1.yml', $mock);
        $this->assertSame($mock, $reflection->invoke($register, $resource));
        // disabled configuration setting on resource
        $resource = new FileResource(__DIR__.'/../Fixtures/test_service1.yml');
        $this->assertSame($real, $reflection->invoke($register, $resource));
    }

    public function testGetInitializedConfiguration()
    {
        $register = $this->getRegisterMock();
        $reflection = new \ReflectionMethod($register, 'getInitializedConfiguration');
        $reflection->setAccessible(true);

        // configuration not set
        try {
            $reflection->invoke($register);
            $this->fail('Expected exception does not occurred.');
        } catch (\Exception $e) {
            $this->assertInstanceOf('\Exception', $e, 'Unexpected exception occurred.');
        }
        // configuration set
        $configuration = new RegisterConfiguration();
        $register->setConfiguration($configuration);
        $this->assertSame($configuration, $reflection->invoke($register));
    }

    /**
     * test for get method
     *
     * @dataProvider getIdProvider
     */
    public function testGetId($methodName, $internalMethodName, $suffix, $expected)
    {
        $register = $this->getRegisterMock(array('buildId', 'buildClassId'));
        $register
            ->expects($this->once())
            ->method($internalMethodName)
            ->with($suffix)
            ->willReturn($expected)
            ;
        $method   = new \ReflectionMethod($register, $methodName);
        $method->setAccessible(true);
        $this->assertSame($expected, $method->invoke($register));
    }

    public function getIdProvider()
    {
        return array(
            array('getYamlLoaderId',          'buildId',      'yaml_file_loader',  'config.yaml_file_loader'),
            array('getXmlLoaderId',           'buildId',      'xml_file_loader',   'config.xml_file_loader'),
            array('getLoaderResolverId',      'buildId',      'loader_resolver',   'config.loader_resolver'),
            array('getDelegatingLoaderId',    'buildId',      'delegating_loader', 'config.delegatingloader'),
            array('getPhpFileCacheClass',     'buildClassId', 'php_file_cache',    'config.php_file_cache.class'),
            array('getConfigCacheClass',      'buildClassId', 'config_cache',      'config.config_cache.class'),
            array('getYamlFileLoaderClass',   'buildClassId', 'yaml_file_loader',  'config.yaml_file_loader.class'),
            array('getXmlFileLoaderClass',    'buildClassId', 'xml_file_loader',   'config.xml_file_loader.class'),
            array('getLoaderResolverClass',   'buildClassId', 'loader_resolver',   'config.loader_resolver.class'),
            array('getDelegatingLoaderClass', 'buildClassId', 'delegating_loader', 'config.delegatingloader.class'),
        );
    }
}
