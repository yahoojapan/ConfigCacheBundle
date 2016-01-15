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
use Symfony\Component\Config\Resource\FileResource as BaseFileResource;

/**
 * FileResource represents a resource stored on the filesystem.
 *
 * The resource can be a file or a directory.
 */
class FileResource extends BaseFileResource implements ResourceInterface
{
    protected $resource;
    protected $configuration;

    /**
     * Constructor.
     *
     * @param string                 $resource
     * @param ConfigurationInterface $configuration
     */
    public function __construct($resource, ConfigurationInterface $configuration = null)
    {
        $this->resource      = $resource;
        $this->configuration = $configuration;
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
     * @return FileResource
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
     * @return FileResource
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
        return file_exists($this->resource);
    }
}
