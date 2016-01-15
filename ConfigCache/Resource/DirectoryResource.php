<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache\Resource;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Resource\DirectoryResource as BaseDirectoryResource;

/**
 * DirectoryResource represents a resources stored in a subdirectory tree.
 */
class DirectoryResource extends BaseDirectoryResource implements ResourceInterface
{
    protected $resource;
    protected $configuration;

    /**
     * Constructor.
     *
     * @param string                 $resource      The file path to the resource
     * @param ConfigurationInterface $configuration
     * @param string                 $pattern       A pattern to restrict monitored files
     */
    public function __construct($resource, ConfigurationInterface $configuration = null, $pattern = null)
    {
        $this->resource      = $resource;
        $this->configuration = $configuration;
        $this->pattern       = $pattern;
    }

    /**
     * Gets configuration.
     *
     * @return ConfigurationInterface
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Sets configuration.
     *
     * @param ConfigurationInterface $configuration
     *
     * @return DirectoryResource
     */
    public function setConfiguration(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;

        return $this;
    }

    /**
     * Returns the resource tied to this Resource.
     *
     * @return mixed The resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Sets the resource.
     *
     * @param string $resource
     *
     * @return DirectoryResource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function exists()
    {
        return is_dir($this->resource);
    }
}
