<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache;
use YahooJapan\ConfigCacheBundle\DependencyInjection\Compiler\RegisterPass;
use YahooJapan\ConfigCacheBundle\DependencyInjection\YahooJapanConfigCacheExtension;
use YahooJapan\ConfigCacheBundle\Tests\Functional\Bundle\RegisterBundle\DependencyInjection\RegisterExtension;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class RegisterPassTest extends TestCase
{
    protected $pass;

    protected function setUp()
    {
        parent::setUp();

        $this->pass = new RegisterPass();
    }

    /**
     * Only one test case
     */
    public function testProcess()
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.cache_dir' => 'test_cache_dir',
        )));
        $extension = new RegisterExtension();
        $container->registerExtension($extension);

        $definition = new Definition('YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache');
        $definition->addTag(ConfigCache::TAG_REGISTER, array('resource' => 'sample.yml'));
        $container->setDefinition('register.test', $definition);

        $this->pass->process($container);

        $this->assertConfigCacheDefinition($definition, 'yahoo_japan_config_cache.doctrine.cache.register.test');
    }

    public function testFindBundleName()
    {
        // has attribute
        $serviceId  = 'yahoo_japan_config_cache.test';
        $attributes = array(array('bundle' => $expected = 'service_prefix'));
        $this->assertSame($expected, $this->util->invoke($this->pass, 'findBundleName', $serviceId, $attributes));

        // has no attribute
        $serviceId  = 'yahoo_japan_config_cache.test';
        $attributes = array();
        $expected   = 'yahoo_japan_config_cache';
        $actual     = $this->util->invoke($this->pass, 'findBundleName', $serviceId, $attributes);
        $this->assertSame($expected, $actual);

        // has tags
        $serviceId  = 'yahoo_japan_config_cache.test';
        $attributes = array(
            array('bundle' => $expected = 'service_prefix1'),
            array('bundle' => 'service_prefix2'),
        );
        $this->assertSame($expected, $this->util->invoke($this->pass, 'findBundleName', $serviceId, $attributes));
    }

    /**
     * @dataProvider findExtensionProvider
     */
    public function testFindExtension($name, $hasExtension, $expectedException)
    {
        $this->setExpectedException($expectedException);
        $container = new ContainerBuilder();
        $extension = new YahooJapanConfigCacheExtension();
        if ($hasExtension) {
            $container->registerExtension($extension);
        }
        $actual = $this->util->invoke($this->pass, 'findExtension', $container, $name);
        $this->assertSame($extension, $actual);
    }

    /**
     * @return array($name, $hasExtension, $expectedException)
     */
    public function findExtensionProvider()
    {
        return array(
            // has Extension
            array('yahoo_japan_config_cache', true, null),
            // has no Extension
            array('yahoo_japan_config_cache', false, '\InvalidArgumentException'),
        );
    }

    public function testFindResource()
    {
        // has resource
        $attributes = array(array('resource' => $expected = 'sample.yml'));
        $this->assertSame($expected, $this->util->invoke($this->pass, 'findResource', $attributes));

        // has no resource
        $this->setExpectedException('\InvalidArgumentException');
        $attributes = array();
        $this->util->invoke($this->pass, 'findResource', $attributes);
    }

    /**
     * @dataProvider findPathProvider
     */
    public function testFindPath($resource, $expected, $expectedException)
    {
        $this->setExpectedException($expectedException);
        $extension = new RegisterExtension();
        $this->assertSame(
            $expected,
            realpath($this->util->invoke($this->pass, 'findPath', $extension, $resource))
        );
    }

    /**
     * @return array($resource, $expected, $expectedException)
     */
    public function findPathProvider()
    {
        $expected = realpath(__DIR__.'/../../Functional/Bundle/RegisterBundle//Resources/config/sample.yml');

        return array(
            // has relative path
            array('sample.yml', $expected, null),
            // has absolute path
            array($expected, $expected, null),
            // has no path
            array('not_exist.yml', null, '\InvalidArgumentException'),
        );
    }

    public function testRegisterDoctrineCache()
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.cache_dir' => $cacheDir = 'test_cache_dir',
        )));
        $serviceId       = 'yahoo_japan_config_cache.test';
        $bundleName      = 'yahoo_japan_config_cache';
        $doctrineCacheId = $this->util->invoke($this->pass, 'registerDoctrineCache', $container, $serviceId, $bundleName);

        $this->assertTrue($container->hasDefinition($doctrineCacheId));
        $definition = $container->findDefinition($doctrineCacheId);
        $this->assertSame($cacheDir."/{$bundleName}", $definition->getArgument(0));
    }

    public function testRegisterConfigCache()
    {
        $container       = new ContainerBuilder();
        $configCacheId   = 'yahoo_japan_config_cache.test';
        $doctrineCacheId = 'yahoo_japan_config_cache.doctrine.cache';
        $path            = 'path/to/file.yml';

        $definition = new Definition('YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache');
        $container->setDefinition($configCacheId, $definition);

        $this->util->invoke($this->pass, 'registerConfigCache', $container, $configCacheId, $doctrineCacheId, $path);

        $this->assertConfigCacheDefinition($definition, $doctrineCacheId);
    }

    protected function assertConfigCacheDefinition(Definition $definition, $doctrineCacheId)
    {
        // arguments
        $arguments = $definition->getArguments();
        $this->assertSame(2, count($arguments));
        $this->assertEquals(new Reference($doctrineCacheId), $arguments[0]);
        $this->assertEquals(new Reference('yahoo_japan_config_cache.delegating_loader'), $arguments[1]);
        // method calls
        $methodCalls = $definition->getMethodCalls();
        $this->assertSame(4, count($methodCalls));
        // only assert method name
        $this->assertSame('setArrayAccess', $methodCalls[0][0]);
        $this->assertSame('addResource', $methodCalls[1][0]);
        $this->assertSame('setStrict', $methodCalls[2][0]);
        $this->assertSame('setId', $methodCalls[3][0]);
        // tags
        $this->assertTrue(array_key_exists(ConfigCache::TAG_CACHE_WARMER, $definition->getTags()));
    }
}
