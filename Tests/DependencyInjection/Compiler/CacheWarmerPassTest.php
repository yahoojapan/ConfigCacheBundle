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
use YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache;
use YahooJapan\ConfigCacheBundle\DependencyInjection\Compiler\CacheWarmerPass;

class CacheWarmerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider processProvider
     */
    public function testProcess($hasWarmer, $configIds)
    {
        $warmerId  = 'yahoo_japan_config_cache.cache_warmer';
        $container = new ContainerBuilder();

        foreach ($configIds as $configId) {
            $definition = new Definition($configId);
            $definition->addTag(ConfigCache::TAG_CACHE_WARMER);
            $container->setDefinition($configId, $definition);
        }
        if ($hasWarmer) {
            $definition = new Definition($warmerId);
            $container->setDefinition($warmerId, $definition);
        }

        $pass = new CacheWarmerPass();
        $pass->process($container);

        foreach ($configIds as $index => $configId) {
            if ($hasWarmer) {
                $calls = $container->getDefinition($warmerId)->getMethodCalls();
                $this->assertTrue(isset($calls[$index][0])); // method name
                $this->assertSame('addConfig', $calls[$index][0]);
                $this->assertTrue(isset($calls[$index][1][0])); // argument
                $this->assertEquals(new Reference($configId), $calls[$index][1][0]);
            } else {
                $this->assertFalse($container->hasDefinition($warmerId));
            }
        }
    }

    /**
     * @return array($hasWarmer, $configIds)
     */
    public function processProvider()
    {
        $configIds = array('config.test1', 'config.test2');

        return array(
            // has warmer
            array(true, $configIds),
            // has not warmer
            array(false, $configIds),
        );
    }
}
