<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache\Resource;

use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * FileResource represents a resource stored on the filesystem.
 *
 * The resource can be a file or a directory.
 */
class FileResource implements ResourceInterface
{
    protected $resource;
    protected $configuration;
    protected $alias;
    protected $restore;

    /**
     * Constructor.
     *
     * @param string                 $resource
     * @param ConfigurationInterface $configuration
     * @param string                 $alias
     * @param bool                   $restore
     */
    public function __construct($resource, ConfigurationInterface $configuration = null, $alias = null, $restore = false)
    {
        $this->resource      = $resource;
        $this->configuration = $configuration;
        $this->alias         = $alias;
        $this->restore       = $restore;
    }

    /**
     * Creates a FileResource.
     *
     * @param string                 $resource
     * @param ConfigurationInterface $configuration
     * @param string                 $alias
     * @param bool                   $restore
     *
     * @return FileResource
     */
    public static function create($resource, ConfigurationInterface $configuration = null, $alias = null, $restore = false)
    {
        return new static($resource, $configuration, $alias, $restore);
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
     * Gets an alias (as ConfigCache service name suffix).
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Sets an alias (as ConfigCache service name suffix).
     *
     * @param string $alias
     *
     * @return FileResource
     *
     * @throws \InvalidArgumentException
     */
    public function setAlias($alias)
    {
        if (!is_string($alias)) {
            throw new \InvalidArgumentException("Alias[{$alias}] must be string.");
        }
        $this->alias = $alias;

        return $this;
    }

    /**
     * Whether FileResource has an alias or not.
     *
     * @return bool
     */
    public function hasAlias()
    {
        return !is_null($this->alias) && $this->alias !== '';
    }

    /**
     * Whether this resources is restorable or not.
     *
     * @return bool
     */
    public function isRestorable()
    {
        return $this->restore ? true : false;
    }

    /**
     * Sets restorable.
     *
     * @param bool $restore
     *
     * @return FileResource
     */
    public function setRestorable($restore)
    {
        $this->restore = $restore;

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
