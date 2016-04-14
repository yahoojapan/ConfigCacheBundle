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

use Symfony\Component\Config\Resource\DirectoryResource as BaseDirectoryResource;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\DirectoryResource;
use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\ResourceInterface;

/**
 * DirectoryRegister registers the ConfigCache services with DirectoryResource.
 */
class DirectoryRegister
{
    protected $register;
    protected $resources = array();
    protected $excludes  = array();

    /**
     * Constructor.
     *
     * @param ServiceRegister $register
     */
    public function __construct(ServiceRegister $register)
    {
        $this->register = $register;
    }

    /**
     * Registers a Configuration service.
     */
    public function register()
    {
        foreach ($this->resources as $resource) {
            $container = $this->register->getContainer();
            $container->addResource(new BaseDirectoryResource($resource->getResource()));

            // private configuration definition, finally discarded because of private service
            $idBuilder     = $this->register->getIdBuilder();
            $configuration = $this->register->getConfiguration();
            $privateId     = $idBuilder->buildConfigurationId($configuration->find($resource));
            $this->register->registerConfiguration($privateId, $configuration->find($resource));

            // find files under directories
            $finder = $this->findFiles($resource, $this->excludes);
            foreach ($finder as $file) {
                $container->findDefinition($idBuilder->buildCacheId())
                    ->addMethodCall('addResource', array((string) $file, new Reference($privateId)))
                    ;
            }
        }
    }

    /**
     * Adds a DirectoryResource.
     *
     * @param DirectoryResource $resource
     *
     * @return DirectoryResource
     */
    public function add(DirectoryResource $resource)
    {
        $this->resources[] = $resource;

        return $this;
    }

    /**
     * Whether the resource is enabled or not.
     *
     * @param ResourceInterface $resource
     *
     * @return bool
     */
    public function enabled(ResourceInterface $resource)
    {
        return $resource->exists() && $resource instanceof DirectoryResource;
    }

    /**
     * Whether the Register has a DirectoryResource or not.
     *
     * @return bool
     */
    public function has()
    {
        return $this->resources !== array();
    }

    /**
     * Sets excluded file pathes.
     *
     * @param array $excludes
     *
     * @return DirectoryRegister
     */
    public function setExcludes(array $excludes)
    {
        $this->excludes = $excludes;

        return $this;
    }

    /**
     * Finds files by a DirectoryResource.
     *
     * @param DirectoryResource $resource
     * @param array             $excludes
     *
     * @return Finder
     */
    protected function findFiles(DirectoryResource $resource, $excludes = array())
    {
        $finder = Finder::create()
            ->files()
            ->filter(function (\SplFileInfo $file) use ($excludes) {
                foreach ($excludes as $exclude) {
                    if (strpos($file->getRealPath(), $exclude) !== false) {
                        return false;
                    }
                }

                return true;
            })
            ->in((array) $resource->getResource())
            ->sortByName()
            ;

        return $finder;
    }
}
