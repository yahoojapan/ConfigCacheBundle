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
use YahooJapan\ConfigCacheBundle\DependencyInjection\Compiler\ConfigCachePass;

class ConfigCachePassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider processProvider
     */
    public function testProcess($hasLocales, $locales, $hasListener, $configIds)
    {
        $listenerId = 'yahoo_japan_config_cache.config_cache_listener';

        $container = new ContainerBuilder();
        if ($hasLocales) {
            $container->setParameter('yahoo_japan_config_cache.locales', $locales);
        }
        foreach ($configIds as $configId) {
            $definition = new Definition($configId);
            $definition->addTag(ConfigCache::TAG_LOCALE);
            $container->setDefinition($configId, $definition);
        }
        if ($hasListener) {
            $definition = new Definition($listenerId);
            $container->setDefinition($listenerId, $definition);
        }

        $pass = new ConfigCachePass();
        $pass->process($container);

        foreach ($configIds as $index => $configId) {
            $calls = $container->getDefinition($configId)->getMethodCalls();
            if ($hasLocales) {
                $this->assertTrue(isset($calls[0][0])); // method name
                $this->assertSame('setReferableLocales', $calls[0][0]);
                $this->assertTrue(isset($calls[0][1][0])); // argument
                $this->assertSame($locales, $calls[0][1][0]);
            } else {
                $this->assertFalse(isset($calls[0][0])); // method name
                $this->assertFalse(isset($calls[0][1][0])); // argument
            }

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
     * @return array($hasLocales, $locales, $hasListener, $configIds)
     */
    public function processProvider()
    {
        return array(
            // has no listener
            array(true, array('en', 'uk'), false, array('config.test1', 'config.test2')),
            // has listener
            array(true, array('en', 'uk'), true, array('config.test1', 'config.test2')),
            // has no parameters
            array(false, array('en', 'uk'), false, array('config.test1', 'config.test2')),
        );
    }
}
