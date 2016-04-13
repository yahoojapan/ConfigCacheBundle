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

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use YahooJapan\ConfigCacheBundle\ConfigCache\Register\RegisterFactory;
use YahooJapan\ConfigCacheBundle\ConfigCache\Register\ServiceIdBuilder;
use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\DirectoryResource;
use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\FileResource;
use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\ResourceInterface;

/**
 * Register registers a cache service by some bundles.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class Register
{
    protected $extension;
    protected $configuration;
    protected $container;
    protected $resources;
    protected $file;
    protected $directory;
    protected $idBuilder;
    protected $serviceRegister;
    protected $factory;

    /**
     * Constructor.
     *
     * @param ExtensionInterface $extension An Extension
     * @param ContainerBuilder   $container A ContainerBuilder instance
     * @param array              $resources Array of Resource key and Configuration instance value
     * @param array              $excludes  Exclude file names
     */
    public function __construct(
        ExtensionInterface $extension,
        ContainerBuilder $container,
        array $resources,
        array $excludes = array()
    ) {
        $this->extension = $extension;
        $this->container = $container;
        $this->resources = $resources;

        $this->initialize($excludes);
    }

    /**
     * Registers a service by a bundle.
     */
    public function register()
    {
        $this
            ->initializeResources()
            ->registerInternal()
            ;
    }

    /**
     * Registers a service by all bundles.
     */
    public function registerAll()
    {
        $this
            ->initializeAllResources($this->container->getParameter('kernel.bundles'))
            ->registerInternal()
            ;
    }

    /**
     * Sets a master configuration.
     *
     * @param ConfigurationInterface $configuration
     *
     * @return Register
     */
    public function setConfiguration(ConfigurationInterface $configuration)
    {
        $this->configuration->setConfiguration($configuration);

        return $this;
    }

    /**
     * Sets an application config.
     *
     * @param array $appConfig
     *
     * @return Register
     */
    public function setAppConfig(array $appConfig)
    {
        $this->serviceRegister->setAppConfig($appConfig);

        return $this;
    }

    /**
     * Sets a config_cache service tag.
     *
     * @param string $tag
     *
     * @return Register
     */
    public function setTag($tag)
    {
        $this->serviceRegister->setTag($tag);

        return $this;
    }

    /**
     * Initializes.
     *
     * @param array $excludes
     */
    protected function initialize(array $excludes = array())
    {
        $this->factory         = $this->createRegisterFactory();
        $this->idBuilder       = $this->factory->createIdBuilder();
        $this->configuration   = $this->factory->createConfigurationRegister();
        $this->serviceRegister = $this->factory->createServiceRegister($this->container);
        $this->file            = $this->factory->createFileRegister();
        $this->directory       = $this->factory->createDirectoryRegister()->setExcludes($excludes);

        // set bundleId, configuration based on extension
        $this->setBundleId();
        $this->setConfigurationByExtension();

        // validate set data on constructor
        $this->validateResources();
        $this->validateCacheId();
    }

    /**
     * Register services internal processing.
     */
    protected function registerInternal()
    {
        $this->file->register();
        $this->directory->register();
    }

    /**
     * Initializes resources by a bundle.
     *
     * @return Register
     */
    protected function initializeResources()
    {
        foreach ($this->resources as $resource) {
            if ($resource->exists()) {
                if ($resource instanceof DirectoryResource) {
                    $this->directory->add($resource);
                } elseif ($resource instanceof FileResource) {
                    $this->file->add($resource);
                }
            }
        }

        $this->postInitializeResources();

        return $this;
    }

    /**
     * Initializes resources by all bundles.
     *
     * @param array $bundles
     *
     * @return Register
     */
    protected function initializeAllResources(array $bundles)
    {
        // extract resources without FileResource with alias
        $resources = array();
        foreach ($this->resources as $resource) {
            if ($resource instanceof FileResource && $resource->hasAlias()) {
                $this->file->add($resource);
            } else {
                $resources[] = $resource;
            }
        }

        foreach ($bundles as $fqcn) {
            $reflection = new \ReflectionClass($fqcn);
            foreach ($resources as $resource) {
                $path = dirname($reflection->getFilename()).$resource->getResource();
                if (is_dir($path)) {
                    $this->directory->add(new DirectoryResource($path, $this->configuration->find($resource)));
                } elseif (file_exists($path)) {
                    $this->file->add(new FileResource($path, $this->configuration->find($resource)));
                }
            }
        }

        $this->postInitializeResources();

        return $this;
    }

    /**
     * Initializes resources postprocessing.
     */
    protected function postInitializeResources()
    {
        if ($this->file->hasNoAlias() || $this->directory->has()) {
            $this->serviceRegister->registerConfigCache();
        }
    }

    /**
     * Sets bundle ID.
     *
     * @return Register
     */
    protected function setBundleId()
    {
        $this->idBuilder->setBundleId($this->extension->getAlias());

        return $this;
    }

    /**
     * Sets a master configuration by extension.
     */
    protected function setConfigurationByExtension()
    {
        $configuration = $this->extension->getConfiguration(array(), $this->container);
        if ($configuration instanceof ConfigurationInterface) {
            $this->setConfiguration($configuration);
        }

        return $this;
    }

    /**
     * Validates resources.
     *
     * @throws \Exception Throws if the resource is not ResourceInterface.
     */
    protected function validateResources()
    {
        if ($this->resources === array()) {
            throw new \Exception("Resources must be required more than one.");
        }
        foreach ($this->resources as $resource) {
            if (!($resource instanceof ResourceInterface)) {
                throw new \Exception("Resources are not instance of ResourceInterface.");
            }
        }
    }

    /**
     * Validates a cache ID.
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     *
     * @throws \Exception Throws if the cacheId and a bundle name are duplicated.
     */
    protected function validateCacheId()
    {
        foreach ($this->container->getParameter('kernel.bundles') as $className => $fqcn) {
            $id = ServiceIdBuilder::parseServiceId($className);
            $serviceIdPrefix = $this->idBuilder->getPrefix();
            if ($serviceIdPrefix === $id) {
                throw new \Exception(
                    "Cache ID[{$serviceIdPrefix}] and Service ID[{$id}] ".
                    "based Bundle name[{$className}] are duplicated"
                );
            }
        }
    }

    /**
     * Creates a RegisterFactory.
     *
     * @return RegisterFactory
     */
    protected function createRegisterFactory()
    {
        return new RegisterFactory();
    }
}
