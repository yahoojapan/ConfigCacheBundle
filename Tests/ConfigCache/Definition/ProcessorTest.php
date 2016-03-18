<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache\Definition;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use YahooJapan\ConfigCacheBundle\ConfigCache\Definition\Processor;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\ProcessorConfiguration;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class ProcessorTest extends TestCase
{
    protected static $processor;
    protected static $configuration;

    public static function setUpBeforeClass()
    {
        self::$processor     = new Processor();
        self::$configuration = new ProcessorConfiguration();
    }

    /**
     * only assert calling processConfiguration()
     */
    public function testProcessConfiguration()
    {
        $name       = 'YahooJapan\ConfigCacheBundle\ConfigCache\Definition\Processor';
        $processor  = $this->util->createMock($name, array('processConfiguration'));
        $validated  = array('validated');
        $validating = array('validating');
        $node       = self::$configuration->getConfigTreeBuilder()->buildTree();
        $processor
            ->expects($this->once())
            ->method('processConfiguration')
            ->with($validated, $validating, self::$configuration, $node)
            ->willReturn(null)
            ;
        $processor->processConfiguration($validated, $validating, self::$configuration, $node);
    }

    /**
     * @dataProvider processProvider
     */
    public function testProcess(
        $validated,
        $validating,
        $nodeOne,
        $masterNode,
        $expectedArray,
        $expectedNode,
        $expectedException
    ) {
        if (is_string($expectedException) && class_exists($expectedException)) {
            $this->setExpectedException($expectedException);
        }
        list($validated, $masterNode) = self::$processor->process(
            $validated,
            $validating,
            $nodeOne,
            $masterNode
        );

        $this->assertSame($expectedArray, $validated);
        $this->assertEquals($expectedNode, $masterNode);
    }

    /**
     * @return array ($validated, $validating, $node, $masterNode, $expectedArray, $expectedNode, $expectedException)
     */
    public function processProvider()
    {
        return array(
            // #0 has no master node
            array(
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                ),
                array(
                    'test_service' => array(
                        'zzz' => 'www',
                        'xxx' => 'yyy',
                    ),
                ),
                $this->createDefaultNode(),
                null,
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                ),
                $this->createDefaultNode(),
                null,
            ),
            // #1 disagree with root node (invalid array root key)
            array(
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                ),
                array(
                    'invalid_service' => array(
                        'zzz' => 'www',
                        'xxx' => 'yyy',
                    ),
                ),
                $this->createDefaultNode(),
                $this->createDefaultNode(),
                null,
                null,
                '\Exception',
            ),
            // #2 disagree with root node (invalid configuration root key)
            array(
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                ),
                array(
                    'test_service' => array(
                        'zzz' => 'www',
                        'xxx' => 'yyy',
                    ),
                ),
                $this->createDifferentRootNode(),
                $this->createDefaultNode(),
                null,
                null,
                '\Exception',
            ),
            // #3 disagree with root node (invalid node root key)
            array(
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                ),
                array(
                    'test_service' => array(
                        'zzz' => 'www',
                        'xxx' => 'yyy',
                    ),
                ),
                $this->createDefaultNode(),
                $this->createDifferentRootNode(),
                null,
                null,
                '\Exception',
            ),
            // #4 zero node definition, empty array (OK)
            array(
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                ),
                array('test_service' => array()),
                $this->createNoChildNode(),
                $this->createDefaultNode(),
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                ),
                $this->createDefaultNode(),
                null,
            ),
            // #5 zero node definition, not empty array (NG)
            array(
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                ),
                array(
                    'test_service' => array(
                        'zzz' => 'www',
                        'xxx' => 'yyy',
                    ),
                ),
                $this->createNoChildNode(),
                $this->createDefaultNode(),
                null,
                null,
                'Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
            ),
            // #6 one node definition
            array(
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                ),
                array(
                    'test_service' => array(
                        'zzz' => 'www',
                    ),
                ),
                $this->createOneChildNode(),
                $this->createDefaultNode(),
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                    'zzz' => 'www',
                ),
                $this->createDefaultNode(),
                null,
            ),
            // #7 node definitions greater than two
            array(
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                ),
                array(
                    'test_service' => array(
                        'zzz' => 'www',
                        'xxx' => 'yyy',
                    ),
                ),
                $this->createTwoChildrenNode(),
                $this->createDefaultNode(),
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                ),
                $this->createDefaultNode(),
                null,
            ),
            // #8 has the same key validation each master node, a node (OK)
            array(
                array(
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                ),
                array(
                    'test_service' => array(
                        'zzz' => 12345,
                        'xxx' => true,
                    ),
                ),
                // a node validation, merge NG
                $this->createCannotBeOverwrittenNode(),
                // master node validation, merge OK
                $this->createTwoChildrenNode(),
                array(
                    'zzz' => 12345,
                    'xxx' => true,
                ),
                $this->createTwoChildrenNode(),
                // merge OK because of enabled master node setting
                null,
            ),
            // #9 has the same key validation each master node, a node (NG)
            array(
                array(
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                ),
                array(
                    'test_service' => array(
                        'zzz' => 12345,
                        'xxx' => true,
                    ),
                ),
                // a node validation, merge OK
                $this->createTwoChildrenNode(),
                // master node validation, merge NG
                $this->createCannotBeOverwrittenNode(),
                null,
                null,
                // merge NG because of enabled master node setting
                'Symfony\Component\Config\Definition\Exception\ForbiddenOverwriteException',
            ),
            // #10 disallowNewKeysInSubsequentConfigs
            array(
                array(
                    'zzz' => 'www',
                ),
                array(
                    'test_service' => array(
                        'zzz' => 'vvv',
                        'xxx' => 'yyy',
                    ),
                ),
                $this->createTwoChildrenNode(),
                $this->createDisallowNode(),
                null,
                null,
                // merge NG because of enabled master node setting
                'Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
            ),
            // #11 has the same key validation each master node, a node (type validation) (OK)
            array(
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                ),
                array(
                    'test_service' => array(
                        'zzz' => 12345,
                    ),
                ),
                $this->createIntegerNode(),
                $this->createDefaultNode(),
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                    'zzz' => 12345,
                    'xxx' => 'yyy',
                ),
                $this->createDefaultNode(),
                null,
            ),
            // #12 has the same key validation each master node, a node (type validation) (NG)
            array(
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                ),
                array(
                    'test_service' => array(
                        'zzz' => 'vvv',
                    ),
                ),
                $this->createIntegerNode(),
                $this->createDefaultNode(),
                null,
                null,
                'Symfony\Component\Config\Definition\Exception\InvalidTypeException',
            ),
            // #13 has the same key validation each master node, a node (by validate()) (OK)
            array(
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                ),
                array(
                    'test_service' => array(
                        'zzz' => 'vvv',
                    ),
                ),
                $this->createValidateNode(),
                $this->createDefaultNode(),
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                    'zzz' => 'vvv',
                    'xxx' => 'yyy',
                ),
                $this->createDefaultNode(),
                null,
            ),
            // #14 has the same key validation each master node, a node (by validate()) (NG)
            array(
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                ),
                array(
                    'test_service' => array(
                        'zzz' => 'vvv',
                    ),
                ),
                $this->createDefaultNode(),
                $this->createDefaultValidateNode(),
                null,
                null,
                'Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
            ),
        );
    }

    protected function createTreeBuilder($name = 'test_service')
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root($name);

        return array($treeBuilder, $rootNode);
    }

    protected function createDefaultNode()
    {
        list($treeBuilder, $rootNode) = $this->createTreeBuilder();
        $rootNode
            ->children()
                ->scalarNode('aaa')->end()
                ->scalarNode('ccc')->end()
                ->scalarNode('zzz')->end()
                ->scalarNode('xxx')->end()
            ->end()
            ;

        return $treeBuilder->buildTree();
    }

    protected function createDifferentRootNode()
    {
        list($treeBuilder, $rootNode) = $this->createTreeBuilder('invalid_service');
        $rootNode
            ->children()
                ->scalarNode('aaa')->end()
                ->scalarNode('ccc')->end()
                ->scalarNode('zzz')->end()
                ->scalarNode('xxx')->end()
            ->end()
            ;

        return $treeBuilder->buildTree();
    }

    protected function createNoChildNode()
    {
        list($treeBuilder, ) = $this->createTreeBuilder();

        return $treeBuilder->buildTree();
    }

    protected function createOneChildNode()
    {
        list($treeBuilder, $rootNode) = $this->createTreeBuilder();
        $rootNode
            ->children()
                ->scalarNode('zzz')->end()
            ->end()
            ;

        return $treeBuilder->buildTree();
    }

    protected function createTwoChildrenNode()
    {
        list($treeBuilder, $rootNode) = $this->createTreeBuilder();
        $rootNode
            ->children()
                ->scalarNode('zzz')->end()
                ->scalarNode('xxx')->end()
            ->end()
            ;

        return $treeBuilder->buildTree();
    }

    protected function createCannotBeOverwrittenNode()
    {
        list($treeBuilder, $rootNode) = $this->createTreeBuilder();
        $rootNode
            ->children()
                ->scalarNode('zzz')->cannotBeOverwritten()->end()
                ->scalarNode('xxx')->end()
            ->end()
            ;

        return $treeBuilder->buildTree();
    }

    protected function createDisallowNode()
    {
        list($treeBuilder, $rootNode) = $this->createTreeBuilder();
        $rootNode
            ->disallowNewKeysInSubsequentConfigs()
            ->children()
                ->scalarNode('zzz')->end()
                ->scalarNode('xxx')->end()
            ->end()
            ;

        return $treeBuilder->buildTree();
    }

    protected function createIntegerNode()
    {
        list($treeBuilder, $rootNode) = $this->createTreeBuilder();
        $rootNode
            ->children()
                ->integerNode('zzz')->end()
            ->end()
            ;

        return $treeBuilder->buildTree();
    }

    protected function createValidateNode()
    {
        list($treeBuilder, $rootNode) = $this->createTreeBuilder();
        $rootNode
            ->children()
                ->scalarNode('zzz')
                    ->validate()
                        ->ifTrue(function ($v) {
                            return !preg_match('/^[A-Z\/]+$/', $v);
                        })
                        ->thenInvalid('Unexpected node[%s]')
                    ->end()
                ->end()
            ->end()
            ;

        return $treeBuilder->buildTree();
    }

    protected function createDefaultValidateNode()
    {
        list($treeBuilder, $rootNode) = $this->createTreeBuilder();
        $rootNode
            ->children()
                ->scalarNode('aaa')->end()
                ->scalarNode('ccc')->end()
                ->scalarNode('zzz')
                    ->validate()
                        ->ifTrue(function ($v) {
                            return !preg_match('/^[A-Z\/]+$/', $v);
                        })
                        ->thenInvalid('Unexpected node value[%s]')
                    ->end()
                ->end()
                ->scalarNode('xxx')->end()
            ->end()
            ;

        return $treeBuilder->buildTree();
    }
}
