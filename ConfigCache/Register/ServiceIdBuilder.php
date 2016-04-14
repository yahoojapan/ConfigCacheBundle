<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache\Register;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Container;

/**
 * ServiceIdBuilder handles service IDs to build.
 */
class ServiceIdBuilder
{
    // bundle ID (underscored string)
    protected $bundleId;
    // ConfigCache service ID prefix
    protected $prefix = 'config';

    /**
     * Builds a cache service ID.
     *
     * @param array $suffixes ex) array("yahoo_japan_config_cache")
     *
     * @return string ex) "config.yahoo_japan_config_cache"
     */
    public function buildId(array $suffixes)
    {
        return implode('.', array_merge(array($this->prefix), $suffixes));
    }

    /**
     * Builds a cache service ID with bundleId.
     *
     * @param array $suffixes ex) $suffixes = array("suffix"), $this->bundleId = "yahoo_japan_config_cache"
     *
     * @return string ex) "config.yahoo_japan_config_cache.suffix"
     */
    public function buildCacheId(array $suffixes = array())
    {
        return $this->buildId(array_merge(array($this->bundleId), $suffixes));
    }

    /**
     * Builds a configuration private service ID.
     *
     * @param ConfigurationInterface $configuration
     *
     * @return string
     *
     * @note before : Acme\DemoBundle\DependencyInjection\Configuration
     *       after  : acme.demo_bundle.dependency_injection.configuration
     */
    public function buildConfigurationId(ConfigurationInterface $configuration)
    {
        $reflection = new \ReflectionClass($configuration);
        $configId   = Container::underscore(strtr($reflection->getName(), '\\', '_'));

        return $this->buildId(array('configuration', $configId));
    }

    /**
     * Gets a bundleId.
     *
     * @return string
     */
    public function getBundleId()
    {
        return $this->bundleId;
    }

    /**
     * Sets a bundleId.
     *
     * @param string $bundleId
     *
     * @return ServiceIdBuilder
     */
    public function setBundleId($bundleId)
    {
        $this->bundleId = $bundleId;

        return $this;
    }

    /**
     * Gets a prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Sets a prefix.
     *
     * @param string $prefix
     *
     * @return ServiceIdBuilder
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Parses a service ID based on bundle name.
     *
     * @param string $name
     *
     * @return string
     */
    public static function parseServiceId($name)
    {
        return Container::underscore(preg_replace('/Bundle$/', '', $name));
    }
}
