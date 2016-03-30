<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache\Definition;

use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class is the entry point for config normalization/merging/finalization.
 *
 * No extends Symfony Processor because of executing validation on each file configuration.
 */
class Processor
{
    /**
     * Processes an array of configurations.
     *
     * @param array         $validated  merged and validated array
     * @param array         $validating merging and validating array
     * @param NodeInterface $node       merging node
     * @param ArrayNode     $masterNode merged node (master node)
     *
     * @return array list of (validated array, merged node)
     */
    public function process(
        array         $validated,
        array         $validating,
        NodeInterface $node,
        ArrayNode     $masterNode = null
    ) {
        // no setting master node
        if (is_null($masterNode)) {
            // set a node to master node
            $masterNode = $node;

        // has setting
        } else {
            // merge a node to master node
            // enabled master node setting when exists the same key validation
            // check existence for avoid exception trying to set a key that already set
            $childrenAll = $masterNode->getChildren();
            foreach ($node->getChildren() as $name => $child) {
                if (!isset($childrenAll[$name])) {
                    $masterNode->addChild($child);
                }
            }
        }

        // validate root node name, target is merging/validating array
        foreach ($validating as $name => $config) {
            if ($masterNode->getName() !== $name || $node->getName() !== $name) {
                throw new \Exception(
                    sprintf(
                        'Settings root[%s] is different from Configuration root[%s] or part[%s].',
                        $name,
                        $masterNode->getName(),
                        $node->getName()
                    )
                );
            }
        }

        // directly set validated array without normalize/merge/finalize
        $currentConfig = $validated;

        // loop a validating array
        foreach ($validating as $config) {
            // execute a node's normalize to validate key
            $config        = $node->normalize($config);
            // execute a master node's merge to reflect cannotBeOverwritten key setting and so on
            $currentConfig = $masterNode->merge($currentConfig, $config);
        }

        // execute a master node's finalize
        $finalized = $masterNode->finalize($currentConfig);

        return array($finalized, $masterNode);
    }

    /**
     * Processes an array of configurations.
     *
     * @param array                  $validated     merged/validated array
     * @param array                  $validating    merging/validating array
     * @param ConfigurationInterface $configuration configuration
     * @param ArrayNode              $masterNode    master node
     *
     * @return array list of (validated array, merged ArrayNode)
     */
    public function processConfiguration(
        array $validated,
        array $validating,
        ConfigurationInterface $configuration,
        ArrayNode $masterNode = null
    ) {
        return $this->process(
            $validated,
            $validating,
            $configuration->getConfigTreeBuilder()->buildTree(),
            $masterNode
        );
    }
}
