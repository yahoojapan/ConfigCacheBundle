<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Command;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * ConfigurationGenerator generates a code sample of Configuration class.
 */
class ConfigurationGenerator
{
    protected $iterator;
    protected $queue;
    protected $bundle;
    protected $className;
    protected $namespace;
    protected $rootKey;
    protected $width = 4;
    protected $previousDepth = -1;

    /**
     * Constructor.
     *
     * @param array  $config
     * @param string $className
     */
    public function __construct(array $config, Bundle $bundle, $className = 'Configuration')
    {
        $this->bundle    = $bundle;
        $this->className = $className;

        $this->initialize($config, $bundle);
    }

    /**
     * Generates a Configuration file.
     *
     * @return string
     */
    public function generate()
    {
        if (!$this->initialized()) {
            throw new \LogicException('The bundle is not initialized.');
        }

        foreach ($this->iterator as $key => $node) {
            // add children(), end(), and arrayNode(), scalarNode()
            $this->addArrayNodeOnAndOff()->addNodes($key, $node);
            $this->previousDepth = $this->iterator->getDepth();
        }

        // post process
        $this->postIteration();

        return $this->generateConfiguration();
    }

    /**
     * Initializes iterator, namespace, rootKey to generate.
     *
     * @param Bundle $bundle
     * @param string $className
     */
    protected function initialize(array $config, Bundle $bundle)
    {
        $this->iterator  = $this->createIterator($config);
        $this->queue     = $this->createQueue();
        $this->namespace = $this->bundle->getNamespace();
        $this->rootKey   = $this->bundle->getContainerExtension()->getAlias();
    }

    /**
     * Whether the ConfigurationGenerator is initialized or not.
     *
     * @return bool
     */
    protected function initialized()
    {
        return !is_null($this->bundle) && !is_null($this->namespace) && !is_null($this->rootKey);
    }

    /**
     * Adds an arrayNode start and end code.
     *
     * adds arrayNode start/end(children(), end())
     *
     * @return ConfigurationGenerator
     */
    protected function addArrayNodeOnAndOff()
    {
        $currentDepth = $this->iterator->getDepth();

        // start iteration
        if ($this->previousDepth === -1) {
            $this->queue->enqueue($this->codeChildren($this->width));

        // forward into leaf(max one operation)
        } elseif ($this->previousDepth < $currentDepth) {
            $this->queue->enqueue($this->codeChildren($this->getIndentCount()));

        // forward into root(one or more operation)
        } else {
            for ($i = $this->previousDepth; $i > $currentDepth; $i--) {
                $innerIndent = $this->getIndentCount($i);
                $this->queue->enqueue($this->codeEnd($innerIndent));
                $this->queue->enqueue($this->codeEnd($innerIndent - $this->width));
            }
        }

        return $this;
    }

    /**
     * Adds an arrayNode or a scalarNode.
     *
     * @param string $key
     * @param mixed  $node
     *
     * @return ConfigurationGenerator
     */
    protected function addNodes($key, $node)
    {
        $indent = $this->getIndentCount();

        if ($this->iterator->callHasChildren()) {
            if ($node !== array()) {
                $code = $this->codeArrayNode($indent + $this->width, $key);
            } else {
                $code = $this->codeArrayNodeWithPrototype($indent + $this->width, $key);
            }
            $this->queue->enqueue($code);
        } else {
            $this->queue->enqueue($this->codeScalarNode($indent + $this->width, $key));
        }

        return $this;
    }

    /**
     * Post iteration process to add a last end() and a semicolon.
     */
    protected function postIteration()
    {
        for ($i = $this->previousDepth; $i > 0; $i--) {
            $innerIndent = $this->getIndentCount($i);
            $this->queue->enqueue($this->codeEnd($innerIndent));
            $this->queue->enqueue($this->codeEnd($innerIndent - $this->width));
        }
        $this->queue->enqueue($this->codeEnd($this->width));
        $this->queue->enqueue($this->codeSemicolon($this->width));
    }

    /**
     * Generates a Configuration file string.
     *
     * @return string
     */
    protected function generateConfiguration()
    {
        $configuration = <<<CONFIGURATION_PREFIX
<?php

namespace $this->namespace\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class $this->className implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        \$treeBuilder = new TreeBuilder();
        \$rootNode    = \$treeBuilder->root('$this->rootKey');
CONFIGURATION_PREFIX
        .PHP_EOL;
        if (count($this->queue) > 0) {
            $configuration .= '        $rootNode'.PHP_EOL;
        }

        foreach ($this->queue as $code) {
            $configuration .= $code;
        }

        $configuration .= <<<'CONFIGURATION_SUFFIX'

        return $treeBuilder;
    }
}
CONFIGURATION_SUFFIX
        .PHP_EOL;

        return $configuration;
    }

    /**
     * Counts an indent and return indented.
     *
     * @param int $count
     *
     * @return string
     */
    protected function indent($count)
    {
        return str_repeat(' ', $count + $this->width * 2);
    }

    /**
     * Gets an indent count.
     *
     * @param int|null $depth
     *
     * @return int
     */
    protected function getIndentCount($depth = null)
    {
        $depth = is_null($depth) ? $this->iterator->getDepth() : $depth;

        return $depth * $this->width * 2 + $this->width;
    }

    /**
     * Codes a children().
     *
     * @param int $indent
     *
     * @return string
     */
    protected function codeChildren($indent)
    {
        return $this->indent($indent).'->children()'.PHP_EOL;
    }

    /**
     * Codes an end().
     *
     * @param int $indent
     *
     * @return string
     */
    protected function codeEnd($indent)
    {
        return $this->indent($indent).'->end()'.PHP_EOL;
    }

    /**
     * Codes an arrayNode().
     *
     * @param int    $indent
     * @param string $key
     * @param string $addCodes
     *
     * @return string
     */
    protected function codeArrayNode($indent, $key, $addCodes = '')
    {
        $codes = array($this->indent($indent)."->arrayNode('{$key}')", $addCodes, PHP_EOL);

        return implode('', $codes);
    }

    /**
     * Codes an arrayNode() with prototype('scalar').
     *
     * @param int    $indent
     * @param string $key
     *
     * @return string
     */
    protected function codeArrayNodeWithPrototype($indent, $key)
    {
        return $this->codeArrayNode($indent, $key, "->prototype('scalar')->end()->end()");
    }

    /**
     * Codes an scalarNode().
     *
     * @param int    $indent
     * @param string $key
     *
     * @return string
     */
    protected function codeScalarNode($indent, $key)
    {
        return $this->indent($indent)."->scalarNode('{$key}')->end()".PHP_EOL;
    }

    /**
     * Codes an semicolon.
     *
     * @param int $indent
     *
     * @return string
     */
    protected function codeSemicolon($indent)
    {
        return $this->indent($indent).';'.PHP_EOL;
    }

    /**
     * Creates an outer iterator.
     *
     * @param array $config
     *
     * @return \RecursiveIteratorIterator
     */
    protected function createIterator(array $config)
    {
        $innerIterator = $this->createInnerIterator($config);

        return new \RecursiveIteratorIterator($innerIterator, \RecursiveIteratorIterator::SELF_FIRST);
    }

    /**
     * Creates an inner iterator.
     *
     * @param array $config
     *
     * @return \RecursiveArrayIterator
     */
    protected function createInnerIterator(array $config)
    {
        return new \RecursiveArrayIterator($config);
    }

    /**
     * Creates a queue.
     *
     * @return \SplQueue
     */
    protected function createQueue()
    {
        return new \SplQueue();
    }
}
