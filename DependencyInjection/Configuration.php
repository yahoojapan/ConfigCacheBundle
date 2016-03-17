<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpKernel\EventListener\LocaleListener;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('yahoo_japan_config_cache');
        $rootNode
            ->children()
                ->arrayNode('locale')
                    ->children()
                        ->booleanNode('enabled')->defaultValue(false)->end()
                        ->arrayNode('locales')
                            ->prototype('scalar')->end()
                        ->end()
                        ->integerNode('listener_priority')
                            ->defaultValue(0)
                            ->validate()
                                // priority must be less than LocaleListener priority
                                ->ifTrue(function ($priority) {
                                    return $priority >= Configuration::getPriorityMax();
                                })
                                ->thenInvalid('LocaleListener priority[%s] must be less than LocaleListener priority['.Configuration::getPriorityMax().']')
                            ->end()
                        ->end()
                        ->scalarNode('loader')->defaultValue(null)->end()
                    ->end()
                    ->validate()
                        ->ifTrue(function ($locale) {
                            return $locale['enabled'] && $locale['locales'] === array();
                        })
                        ->thenInvalid('yahoo_japan_config_cache.locale.locales must be configured.')
                    ->end()
                ->end()
            ->end()
            ;

        return $treeBuilder;
    }

    /**
     * Gets a listener priority max.
     *
     * The priority max is a LocaleListener priority.
     *
     * @return int
     */
    public static function getPriorityMax()
    {
        $events = LocaleListener::getSubscribedEvents();

        return $events[KernelEvents::REQUEST][0][1];
    }
}
