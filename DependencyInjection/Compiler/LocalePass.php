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
use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCache;

/**
 * Adds tagged config.locale services to config_cache.listener
 */
class LocalePass implements CompilerPassInterface
{
    protected $loaderParameter  = 'yahoo_japan_config_cache.loader';
    protected $localesParameter = 'yahoo_japan_config_cache.locales';
    protected $listenerId       = 'yahoo_japan_config_cache.locale.listener';

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter($this->localesParameter)) {
            return;
        }

        foreach ($container->findTaggedServiceIds(ConfigCache::TAG_LOCALE) as $configId => $attributes) {
            $cacheDefinition = $container->getDefinition($configId);
            $locales = $container->getParameter($this->localesParameter);
            $cacheDefinition->addMethodCall('setReferableLocales', array($locales));

            if ($container->hasDefinition($this->listenerId)) {
                $listenerDefinition = $container->getDefinition($this->listenerId);
                $listenerDefinition->addMethodCall('addConfig', array(new Reference($configId)));
                if ($container->hasParameter($this->loaderParameter)
                    && $container->hasDefinition($loaderId = $container->getParameter($this->loaderParameter))
                ) {
                    $cacheDefinition
                        ->removeMethodCall('setLoader')
                        ->addMethodCall('setLoader', array(new Reference($loaderId)))
                        ;
                }
            }
        }
    }
}
