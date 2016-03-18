<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\Fixtures;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ConfigCacheConfiguration implements ConfigurationInterface
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
                ->scalarNode('ccc')->end()
                ->scalarNode('zzz')->end()
                ->scalarNode('xxx')->end()
            ->end()
            ;

        return $treeBuilder;
    }
}
