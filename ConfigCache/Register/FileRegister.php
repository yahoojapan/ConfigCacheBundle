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
use Symfony\Component\DependencyInjection\Reference;
use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\FileResource;

/**
 * FileRegister registers the ConfigCache services with FileResource.
 */
class FileRegister
{
    protected $serviceRegister;
    protected $resources = array();

    /**
     * Constructor.
     *
     * @param ServiceRegister $serviceRegister
     */
    public function __construct(ServiceRegister $serviceRegister)
    {
        $this->serviceRegister = $serviceRegister;
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
        $standaloneCacheId = $this->serviceRegister->getIdBuilder()->buildCacheId(array($alias));
        $container = $this->serviceRegister->getContainer();
        if ($container->hasDefinition($standaloneCacheId)) {
            throw new \RuntimeException(
                "{$standaloneCacheId} is already registered. Maybe FileResource alias[{$alias}] is duplicated."
            );
        }

        $container->addResource(new BaseFileResource($path));
        $this->serviceRegister->registerConfigCacheByAlias($alias);
        $container->findDefinition($standaloneCacheId)
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
        $container = $this->serviceRegister->getContainer();
        $container->addResource(new BaseFileResource($resource->getResource()));

        // private configuration definition, finally discarded because of private service
        $idBuilder     = $this->serviceRegister->getIdBuilder();
        $configuration = $this->serviceRegister->getConfiguration();
        $privateId     = $idBuilder->buildConfigurationId($configuration->find($resource));
        $this->serviceRegister->registerConfiguration($privateId, $configuration->find($resource));

        $container->findDefinition($idBuilder->buildCacheId())
            ->addMethodCall(
                'addResource',
                array((string) $resource->getResource(), new Reference($privateId))
            )
            ;
    }
}
