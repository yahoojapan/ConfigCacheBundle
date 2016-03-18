<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache;

/**
 * Adds ConfigCache service ids to the ConfigCacheListener property for warming up caches.
 */
class CacheWarmerPass implements CompilerPassInterface
{
    protected $warmerId = 'yahoo_japan_config_cache.cache_warmer';

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->warmerId)) {
            return;
        }
        $definition = $container->getDefinition($this->warmerId);

        foreach ($container->findTaggedServiceIds(ConfigCache::TAG_CACHE_WARMER) as $configId => $attributes) {
            $definition->addMethodCall('addConfig', array(new Reference($configId)));
        }
    }
}
