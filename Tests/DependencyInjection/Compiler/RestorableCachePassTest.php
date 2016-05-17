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
use Symfony\Component\DependencyInjection\Reference;
use YahooJapan\ConfigCacheBundle\ConfigCache\RestorablePhpFileCache;
use YahooJapan\ConfigCacheBundle\DependencyInjection\Compiler\RestorableCachePass;

class RestorableCachePassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider processProvider
     */
    public function testProcess($hasRestorer, $hasSaver, $configIds)
    {
        $restorerId = 'yahoo_japan_config_cache.cache_restorer';
        $saverId    = 'yahoo_japan_config_cache.cache_saver';
        $container  = new ContainerBuilder();

        foreach ($configIds as $configId) {
            $definition = new Definition($configId);
            $definition->addTag(RestorablePhpFileCache::TAG_RESTORABLE_CACHE);
            $container->setDefinition($configId, $definition);
        }
        if ($hasRestorer) {
            $definition = new Definition($restorerId);
            $container->setDefinition($restorerId, $definition);
        }
        if ($hasSaver) {
            $definition = new Definition($saverId);
            $container->setDefinition($saverId, $definition);
        }

        $pass = new RestorableCachePass();
        $pass->process($container);

        foreach ($configIds as $index => $configId) {
            if ($hasRestorer && $hasSaver) {
                foreach (array($restorerId, $saverId) as $id) {
                    $calls = $container->getDefinition($id)->getMethodCalls();
                    $this->assertTrue(isset($calls[$index][0])); // method name
                    $this->assertSame('addConfig', $calls[$index][0]);
                    $this->assertTrue(isset($calls[$index][1][0])); // argument
                    $this->assertEquals(new Reference($configId), $calls[$index][1][0]);
                }
            } else {
                if ($hasRestorer) {
                    $this->assertSame(array(), $container->getDefinition($restorerId)->getMethodCalls());
                }
                if ($hasSaver) {
                    $this->assertSame(array(), $container->getDefinition($saverId)->getMethodCalls());
                }
            }
        }
    }

    /**
     * @return array($hasRestorer, $hasSaver, $configIds)
     */
    public function processProvider()
    {
        $configIds = array('config.test1', 'config.test2');

        return array(
            // has restorer, saver
            array(true, true, $configIds),
            // no restorer
            array(false, true, $configIds),
            // no saver
            array(true, false, $configIds),
        );
    }
}
