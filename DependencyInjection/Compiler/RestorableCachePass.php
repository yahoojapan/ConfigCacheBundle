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
use YahooJapan\ConfigCacheBundle\ConfigCache\RestorablePhpFileCache;

/**
 * RestorableCachePass is a service register pass to recreate the caches.
 */
class RestorableCachePass implements CompilerPassInterface
{
    protected $restorerId = 'yahoo_japan_config_cache.cache_restorer';
    protected $saverId    = 'yahoo_japan_config_cache.cache_saver';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->restorerId) || !$container->hasDefinition($this->saverId)) {
            return;
        }
        $restorer = $container->getDefinition($this->restorerId);
        $saver    = $container->getDefinition($this->saverId);

        foreach ($container->findTaggedServiceIds(RestorablePhpFileCache::TAG_RESTORABLE_CACHE) as $configCacheId => $attributes) {
            $restorer->addMethodCall('addConfig', array(new Reference($configCacheId)));
            $saver->addMethodCall('addConfig', array(new Reference($configCacheId)));
        }
    }
}
