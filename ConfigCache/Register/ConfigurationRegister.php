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
use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\ResourceInterface;

/**
 * ConfigurationRegister handles a configuration to validate files.
 */
class ConfigurationRegister
{
    protected $configuration;

    /**
     * Finds a configuration.
     *
     * @return ConfigurationInterface
     */
    public function find(ResourceInterface $resource)
    {
        return $resource->getConfiguration() ?: $this->findInitialized();
    }

    /**
     * Finds a initialized configuration.
     *
     * @return ConfigurationInterface
     *
     * @throws \Exception thrown if the configuration is not set.
     */
    public function findInitialized()
    {
        if (is_null($this->configuration)) {
            throw new \Exception('The Configuration must be set.');
        }

        return $this->configuration;
    }

    /**
     * Sets a Configuration.
     *
     * @param ConfigurationInterface $configuration
     *
     * @return ConfigurationRegister
     */
    public function setConfiguration(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;

        return $this;
    }
}
