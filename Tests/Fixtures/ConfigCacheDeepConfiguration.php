<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\Fixtures;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ConfigCacheDeepConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('test_service');
        $rootNode
            ->children()
                ->scalarNode('aaa')->end()
                ->arrayNode('ccc')
                    ->children()
                        ->scalarNode('ccc_aaa')->end()
                        ->arrayNode('ccc_ccc')
                            ->children()
                                ->scalarNode('ccc_ccc_aaa')->end()
                                ->scalarNode('ccc_ccc_ccc')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('eee')->end()
            ->end()
            ;

        return $treeBuilder;
    }
}
