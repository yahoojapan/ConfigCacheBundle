<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCache;
use YahooJapan\ConfigCacheBundle\DependencyInjection\Compiler\LocalePass;

class LocalePassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider processProvider
     */
    public function testProcess(
        $hasLocales,
        $locales,
        $hasListener,
        $configIds,
        $hasLoaderParameter,
        $hasLoaderDefinition,
        $replaceLoader
    ) {
        $listenerId       = 'yahoo_japan_config_cache.config_cache_listener';
        $defaultLoaderId  = 'yahoo_japan_config_cache.loader';
        $replacedLoaderId = 'test_loader';

        $container = new ContainerBuilder();
        if ($hasLocales) {
            $container->setParameter('yahoo_japan_config_cache.locales', $locales);
        }
        foreach ($configIds as $configId) {
            $definition = new Definition($configId);
            $definition
                ->addTag(ConfigCache::TAG_LOCALE)
                ->addMethodCall('setLoader', array(new Reference($defaultLoaderId)))
                ;
            $container->setDefinition($configId, $definition);
        }
        if ($hasListener) {
            $definition = new Definition($listenerId);
            $container->setDefinition($listenerId, $definition);
            if ($hasLoaderParameter) {
                $container->setParameter('yahoo_japan_config_cache.loader', $replacedLoaderId);
            }
            if ($hasLoaderDefinition) {
                $definition = new Definition($replacedLoaderId);
                $container->setDefinition($replacedLoaderId, $definition);
            }
        }

        $pass = new LocalePass();
        $pass->process($container);

        foreach ($configIds as $index => $configId) {
            $calls = $container->getDefinition($configId)->getMethodCalls();

            // locale
            if ($hasLocales) {
                $this->assertTrue(isset($calls[1][0])); // method name
                $this->assertSame('setReferableLocales', $calls[1][0]);
                $this->assertTrue(isset($calls[1][1][0])); // argument
                $this->assertSame($locales, $calls[1][1][0]);
            } else {
                $this->assertFalse(isset($calls[1][0])); // method name
                $this->assertFalse(isset($calls[1][1][0])); // argument
            }

            // loader
            if ($replaceLoader) {
                $loaderIndex = 2;
                $loaderId    = $replacedLoaderId;
            } else {
                $loaderIndex = 0;
                $loaderId    = $defaultLoaderId;
            }
            $this->assertTrue(isset($calls[$loaderIndex][0])); // method name
            $this->assertSame('setLoader', $calls[$loaderIndex][0]);
            $this->assertTrue(isset($calls[$loaderIndex][1][0])); // argument
            $this->assertEquals(new Reference($loaderId), $calls[$loaderIndex][1][0]);

            // listener
            if ($hasListener) {
                $calls = $container->getDefinition($listenerId)->getMethodCalls();
                $this->assertTrue(isset($calls[$index][0])); // method name
                $this->assertSame('addConfig', $calls[$index][0]);
                $this->assertTrue(isset($calls[$index][1][0])); // argument
                $this->assertEquals(new Reference($configId), $calls[$index][1][0]);
            } else {
                $this->assertFalse($container->hasDefinition($listenerId));
            }
        }
    }

    /**
     * @return array($hasLocales, $locales, $hasListener, $configIds, $hasLoaderParameter, $hasLoaderDefinition, $replaceLoader)
     */
    public function processProvider()
    {
        $locales   = array('en', 'uk');
        $configIds = array('config.test1', 'config.test2');

        return array(
            // has no listener
            array(true, $locales, false, $configIds, false, false, false),
            // has listener
            array(true, $locales, true, $configIds, false, false, false),
            // has no parameters
            array(false, $locales, false, $configIds, false, false, false),
            // has loader parameter, has no definition (not replaced)
            array(true, $locales, true, $configIds, true, false, false),
            // has loader parameter, has definition (replaced)
            array(true, $locales, true, $configIds, true, true, true),
        );
    }
}
