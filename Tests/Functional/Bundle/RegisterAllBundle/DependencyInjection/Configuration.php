<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\Functional\Bundle\RegisterAllBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('register_all');
        $rootNode
            ->children()
                ->integerNode('invoice')->end()
                ->scalarNode('date')->end()
                ->arrayNode('bill_to')
                    ->children()
                        ->scalarNode('given')->end()
                        ->scalarNode('family')->end()
                    ->end()
                ->end()
                ->arrayNode('ship_to')
                    ->children()
                        ->scalarNode('given')->end()
                        ->scalarNode('family')->end()
                    ->end()
                ->end()
            ->end()
            ;

        return $treeBuilder;
    }
}
