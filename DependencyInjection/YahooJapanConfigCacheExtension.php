<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class YahooJapanConfigCacheExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        if (!$config['cache_warmup']) {
            $container->removeDefinition('yahoo_japan_config_cache.cache_warmer');
        }

        if (isset($config['locale']) && $this->isConfigEnabled($container, $config['locale'])) {
            $container->setParameter('yahoo_japan_config_cache.locales', $config['locale']['locales']);
            $container->setParameter('yahoo_japan_config_cache.listener_priority', $config['locale']['listener_priority']);
            $container->setParameter('yahoo_japan_config_cache.loader', $config['locale']['loader']);
        } else {
            $container->removeDefinition('yahoo_japan_config_cache.locale_listener');
        }
    }
}
