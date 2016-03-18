<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache;

use Doctrine\Common\Cache\Cache;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use YahooJapan\ConfigCacheBundle\ConfigCache\Definition\Processor;
use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\FileResource;
use YahooJapan\ConfigCacheBundle\ConfigCache\Util\ArrayAccessInterface;

/**
 * ConfigCache manages user-defined configuration files.
 */
class ConfigCache
{
    const DEFAULT_KEY      = 'cache';
    const TAG_CACHE_WARMER = 'config_cache.warmer';

    protected $cache;
    protected $loader;
    protected $config = array();
    protected $arrayAccess;
    protected $configuration;
    protected $resources = array();
    protected $key;
    protected $strict = true;

    /**
     * Constructor.
     *
     * @param Cache           $cache
     * @param LoaderInterface $loader
     * @param array           $config
     */
    public function __construct(Cache $cache, LoaderInterface $loader, array $config = array())
    {
        $this->cache  = $cache;
        $this->loader = $loader;
        $this->config = $config;
    }

    /**
     * Sets a loader.
     *
     * @param LoaderInterface $loader
     *
     * @return ConfigCache
     */
    public function setLoader(LoaderInterface $loader)
    {
        $this->loader = $loader;

        return $this;
    }

    /**
     * Sets a ArrayAccess to find array value using dotted key.
     *
     * @param ArrayAccessInterface $arrayAccess
     *
     * @return ConfigCache
     */
    public function setArrayAccess(ArrayAccessInterface $arrayAccess)
    {
        $this->arrayAccess = $arrayAccess;

        return $this;
    }

    /**
     * Adds a resource.
     *
     * @param string                      $resource
     * @param ConfigurationInterface|null $configuration
     *
     * @return ConfigCache
     */
    public function addResource($resource, ConfigurationInterface $configuration = null)
    {
        $this->resources[] = new FileResource($resource, $configuration);

        return $this;
    }

    /**
     * Sets a configuration.
     *
     * @param ConfigurationInterface $configuration
     *
     * @return ConfigCache
     */
    public function setConfiguration(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;

        return $this;
    }

    /**
     * Sets a key (only once).
     *
     * @param string $key
     *
     * @return ConfigCache
     *
     * @throws \RuntimeException
     */
    public function setKey($key)
    {
        if (!is_null($this->key)) {
            throw new \RuntimeException('The key must not be set if already set.');
        }

        $this->key = $key;

        return $this;
    }

    /**
     * Sets a strict mode.
     *
     * @param bool $strict
     *
     * @return ConfigCache
     */
    public function setStrict($strict)
    {
        $this->strict = $strict;

        return $this;
    }

    /**
     * Finds cached array.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return array
     */
    public function find($key, $default = array())
    {
        return $this->findInternal($this->findAll(), $key, $default);
    }

    /**
     * Finds All cached array.
     *
     * @return array
     */
    public function findAll()
    {
        $data = $this->cache->fetch($this->getKey());
        if (!$data) {
            $data = $this->createInternal();
        }

        return $data;
    }

    /**
     * Creates PHP cache file.
     */
    public function create()
    {
        if (!$this->cache->contains($this->getKey())) {
            $this->createInternal();
        }
    }

    /**
     * Creates PHP cache file internal processing.
     *
     * @return array
     */
    protected function createInternal()
    {
        $data = $this->load();
        $this->cache->save($this->getKey(), $data);

        return $data;
    }

    /**
     * Gets a key.
     *
     * @return string
     */
    protected function getKey()
    {
        return $this->key ?: static::DEFAULT_KEY;
    }

    /**
     * Whether the mode is strict or not.
     *
     * @return bool
     */
    protected function isStrict()
    {
        return $this->strict;
    }

    /**
     * Finds cached array internal processing.
     *
     * @param array  $data
     * @param string $key
     * @param mixed  $default
     *
     * @return array
     */
    protected function findInternal(array $data, $key, $default = array())
    {
        $result = array();

        if (is_null($this->arrayAccess)) {
            $result = isset($data[$key]) ? $data[$key] : $default;
        } else {
            $result = $this->arrayAccess->replace($data)->get($key, $default);
        }

        return $result;
    }

    /**
     * Loads config files.
     *
     * @return array
     */
    protected function load()
    {
        if ($this->resources === array()) {
            throw new \Exception('No added resources.');
        }
        if (!$this->isStrict() && count($this->resources) === 1) {
            return $this->loadOne();
        }

        $loaded     = $this->config;
        $masterNode = $this->createMasterNode();

        foreach ($this->resources as $file) {
            list($loaded, $masterNode) = $this->processConfiguration(
                $loaded,
                $this->loader->load($file->getResource()),
                $file->getConfiguration(),
                $masterNode
            );
        }

        return $loaded;
    }

    /**
     * Loads a config file which is not strict mode.
     *
     * @return array
     */
    protected function loadOne()
    {
        return $this->loader->load($this->resources[0]->getResource());
    }

    /**
     * Processes an array of configurations.
     *
     * @param array                  $validated     validated array
     * @param array                  $validating    validating array
     * @param ConfigurationInterface $configuration configuration
     * @param ArrayNode              $masterNode    master node
     *
     * @return array list of (array, ArrayNode)
     */
    protected function processConfiguration(
        array $validated,
        array $validating,
        ConfigurationInterface $configuration,
        ArrayNode $masterNode = null
    ) {
        $processor = new Processor();

        return $processor->processConfiguration(
            $validated,
            $validating,
            $configuration,
            $masterNode
        );
    }

    /**
     * Creates master node.
     *
     * @param ConfigurationInterface $configuration
     *
     * @return ArrayNode
     */
    protected function createMasterNode()
    {
        if (is_null($this->configuration)) {
            return $this->configuration;
        } else {
            return $this->configuration->getConfigTreeBuilder()->buildTree();
        }
    }
}
