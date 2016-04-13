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

use Symfony\Component\Config\Resource\FileResource as BaseFileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\FileResource;

/**
 * FileRegister registers the ConfigCache services with FileResource.
 */
class FileRegister
{
    protected $container;
    protected $idBuilder;
    protected $serviceRegister;
    protected $configuration;
    protected $resources = array();

    /**
     * Constructor.
     *
     * @param ContainerBuilder      $container
     * @param ServiceIdBuilder      $idBuilder
     * @param ServiceRegister       $serviceRegister
     * @param ConfigurationRegister $configuration
     */
    public function __construct(
        ContainerBuilder      $container,
        ServiceIdBuilder      $idBuilder,
        ServiceRegister       $serviceRegister,
        ConfigurationRegister $configuration
    ) {
        $this->container       = $container;
        $this->idBuilder       = $idBuilder;
        $this->serviceRegister = $serviceRegister;
        $this->configuration   = $configuration;
    }

    /**
     * Registers ConfigCache or Configuration services
     */
    public function register()
    {
        foreach ($this->resources as $resource) {
            if ($resource->hasAlias()) {
                $this->registerConfigCache($resource);
            } else {
                $this->registerConfiguration($resource);
            }
        }
    }

    /**
     * Adds a FileResource.
     *
     * @param FileResource $resource
     *
     * @return FileRegister
     */
    public function add(FileResource $resource)
    {
        $this->resources[] = $resource;

        return $this;
    }

    /**
     * Whether the Register has a FileResource without alias or not.
     *
     * @return bool
     */
    public function hasNoAlias()
    {
        foreach ($this->resources as $resource) {
            if (!$resource->hasAlias()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Registers a ConfigCache service.
     *
     * @param FileResource $resource
     *
     * @throws \RuntimeException throws if the ConfigCache service ID already exists
     */
    protected function registerConfigCache(FileResource $resource)
    {
        $alias = $resource->getAlias();
        $path  = $resource->getResource();
        $standaloneCacheId = $this->idBuilder->buildCacheId(array($alias));
        if ($this->container->hasDefinition($standaloneCacheId)) {
            throw new \RuntimeException(
                "{$standaloneCacheId} is already registered. Maybe FileResource alias[{$alias}] is duplicated."
            );
        }

        $this->container->addResource(new BaseFileResource($path));
        $this->serviceRegister->registerConfigCacheByAlias($alias);
        $this->container->findDefinition($standaloneCacheId)
            ->addMethodCall('addResource', array((string) $path))
            ->addMethodCall('setStrict', array(false))
            ->addMethodCall('setKey', array($alias))
            ;
    }

    /**
     * Registers a Configuration service.
     *
     * @param FileResource $resource
     */
    protected function registerConfiguration(FileResource $resource)
    {
        $this->container->addResource(new BaseFileResource($resource->getResource()));

        // private configuration definition, finally discarded because of private service
        $privateId = $this->idBuilder->buildConfigurationId($this->configuration->find($resource));
        $this->serviceRegister->registerConfiguration($privateId, $this->configuration->find($resource));

        $this->container->findDefinition($this->idBuilder->buildCacheId())
            ->addMethodCall(
                'addResource',
                array((string) $resource->getResource(), new Reference($privateId))
            )
            ;
    }
}
