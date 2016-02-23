<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Resource\DirectoryResource as BaseDirectoryResource;
use Symfony\Component\Config\Resource\FileResource as BaseFileResource;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
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
    protected $excludes;
    protected $dirs      = array();
    protected $files     = array();
    protected $appConfig = array();
    // bundle ID
    protected $bundleId;
    // ConfigCache service ID
    protected $cacheId = 'config';
    // ConfigCache service tag
    protected $tag;

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
        $this->excludes  = $excludes;

        $this->initialize();
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
     */
    public function setConfiguration(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;

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
        $this->appConfig = $appConfig;

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
        $this->tag = $tag;

        return $this;
    }

    /**
     * Initialize.
     */
    protected function initialize()
    {
        // set bundleId, configuration based on extension
        $this->setBundleId();
        $this->setConfigurationByExtension();

        // validate set data on constructor
        $this->validateResources();
        $this->validateCacheId();

        // preprocess registerInternal()
        $this->setLoaderDefinition();
    }

    /**
     * Register services internal processing.
     */
    protected function registerInternal()
    {
        $cacheId = $this->buildId($this->bundleId);

        foreach ($this->dirs as $resource) {
            $this->container->addResource(new BaseDirectoryResource($resource->getResource()));

            // private configuration definition, finally discarded because of private service
            $privateId = $this->buildConfigurationId($this->findConfigurationByResource($resource));
            $this->setConfigurationDefinition($privateId, $this->findConfigurationByResource($resource));

            // find files under directories
            $finder = $this->findFilesByDirectory($resource, $this->excludes);
            foreach ($finder as $file) {
                $this->container->findDefinition($cacheId)
                    ->addMethodCall('addResource', array((string) $file, new Reference($privateId)))
                    ;
            }
        }

        foreach ($this->files as $resource) {
            if ($resource->hasAlias()) {
                $alias = $resource->getAlias();
                $path  = $resource->getResource();
                $standaloneCacheId = $this->buildId(array($this->bundleId, $alias));
                if ($this->container->hasDefinition($standaloneCacheId)) {
                    throw new \RuntimeException(
                        "{$standaloneCacheId} is already registered. Maybe FileResource alias[{$alias}] is duplicated."
                    );
                }

                $this->container->addResource(new BaseFileResource($path));
                $this->setCacheDefinitionByAlias($alias);
                $this->container->findDefinition($standaloneCacheId)
                    ->addMethodCall('addResource', array((string) $path))
                    ->addMethodCall('setStrict', array(false))
                    ->addMethodCall('setKey', array($alias))
                    ;
            } else {
                $this->container->addResource(new BaseFileResource($resource->getResource()));

                // private configuration definition, finally discarded because of private service
                $privateId = $this->buildConfigurationId($this->findConfigurationByResource($resource));
                $this->setConfigurationDefinition($privateId, $this->findConfigurationByResource($resource));

                $this->container->findDefinition($cacheId)
                    ->addMethodCall(
                        'addResource',
                        array((string) $resource->getResource(), new Reference($privateId))
                    )
                    ;
            }
        }
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
                    $this->addDirectory($resource);
                } elseif ($resource instanceof FileResource) {
                    $this->addFile($resource);
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
                $this->addFile($resource);
            } else {
                $resources[] = $resource;
            }
        }

        foreach ($bundles as $fqcn) {
            $reflection = new \ReflectionClass($fqcn);
            foreach ($resources as $resource) {
                $path = dirname($reflection->getFilename()).$resource->getResource();
                if (is_dir($path)) {
                    $this->addDirectory(new DirectoryResource($path, $this->findConfigurationByResource($resource)));
                } elseif (file_exists($path)) {
                    $this->addFile(new FileResource($path, $this->findConfigurationByResource($resource)));
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
        if ($this->hasFileResourcesWithoutAlias() || count($this->dirs) > 0) {
            $this->setCacheDefinition();
        }
    }

    /**
     * Whether Register has a FileResource without alias or not.
     *
     * @return bool
     */
    protected function hasFileResourcesWithoutAlias()
    {
        foreach ($this->files as $resource) {
            if (!$resource->hasAlias()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Finds files by a directory.
     *
     * @param DirectoryResource $resource
     * @param array             $excludes
     *
     * @return Finder
     */
    protected function findFilesByDirectory(DirectoryResource $resource, $excludes = array())
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

    /**
     * Sets bundle ID.
     *
     * @return Register
     */
    protected function setBundleId()
    {
        $this->bundleId = $this->extension->getAlias();

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
     * Sets a cache definition.
     */
    protected function setCacheDefinition()
    {
        $id         = $this->buildId($this->bundleId);
        $definition = $this->createCacheDefinition();
        $this->addConfigurationMethod($definition);
        $this->container->setDefinition($id, $definition);
    }

    /**
     * Sets a cache definition by alias (a service name).
     *
     * @param string $alias
     */
    protected function setCacheDefinitionByAlias($alias)
    {
        $id         = $this->buildId(array($this->bundleId, $alias));
        $definition = $this->createCacheDefinition();
        $this->container->setDefinition($id, $definition);
    }

    /**
     * Creates a cache definition without Configuration Reference.
     *
     * @return Definition
     */
    protected function createCacheDefinition()
    {
        // doctrine/cache
        $cache = new Definition(
            $this->container->getParameter('config.php_file_cache.class'),
            array(
                $this->container->getParameter('kernel.cache_dir')."/{$this->bundleId}",
                '.php',
            )
        );
        $cache->setPublic(false);
        $cacheId = $this->buildId(array('doctrine', 'cache', $this->bundleId));
        $this->container->setDefinition($cacheId, $cache);

        // ArrayAccess
        $arrayAccessId = $this->container->getParameter('config.array_access.id');

        // user cache
        $definition = new Definition(
            $this->container->getParameter('config.config_cache.class'),
            array(
                new Reference($cacheId),
                new Reference($this->container->getParameter('config.delegating_loader.id')),
                $this->appConfig,
            )
        );
        $definition
            ->setLazy(true)
            ->addMethodCall('setArrayAccess', array(new Reference($arrayAccessId)))
            ;
        if (!is_null($this->tag)) {
            $definition->addTag($this->tag);
        }

        return $definition;
    }

    /**
     * Adds a Configuration set method to Definition.
     *
     * @return Definition
     */
    protected function addConfigurationMethod(Definition $definition)
    {
        // master configuration
        $configId = $this->buildConfigurationId($this->getInitializedConfiguration());
        $this->setConfigurationDefinition($configId, $this->getInitializedConfiguration());
        $definition->addMethodCall('setConfiguration', array(new Reference($configId)));

        return $definition;
    }

    /**
     * Sets a configuration definition.
     *
     * @param string                 $configId
     * @param ConfigurationInterface $configuration
     */
    protected function setConfigurationDefinition($configId, ConfigurationInterface $configuration)
    {
        if (!$this->container->hasDefinition($configId)) {
            $reflection       = new \ReflectionClass($configuration);
            $configDefinition = new Definition($reflection->getName());
            $configDefinition->setPublic(false);
            $this->container->setDefinition($configId, $configDefinition);
        }
    }

    /**
     * Sets a loader definition.
     */
    protected function setLoaderDefinition()
    {
        $yamlLoader = new Definition($this->container->getParameter('config.yaml_file_loader.class'));
        $xmlLoader  = new Definition($this->container->getParameter('config.xml_file_loader.class'));
        $yamlLoader->setPublic(false);
        $xmlLoader->setPublic(false);
        $yamlLoaderId = $this->container->getParameter('config.yaml_file_loader.id');
        $xmlLoaderId  = $this->container->getParameter('config.xml_file_loader.id');

        if ($this->container->hasDefinition($yamlLoaderId)) {
            throw new \Exception(sprintf("Service[%s] already registered.", $yamlLoaderId));
        }
        if ($this->container->hasDefinition($xmlLoaderId)) {
            throw new \Exception(sprintf("Service[%s] already registered.", $xmlLoaderId));
        }
        $this->container->setDefinition($yamlLoaderId, $yamlLoader);
        $this->container->setDefinition($xmlLoaderId, $xmlLoader);

        $resolver = new Definition(
            $this->container->getParameter('config.loader_resolver.class'),
            array(array(
                new Reference($yamlLoaderId),
                new Reference($xmlLoaderId),
            ))
        );
        $resolver->setPublic(false);
        $resolverId = $this->container->getParameter('config.loader_resolver.id');
        if ($this->container->hasDefinition($resolverId)) {
            throw new \Exception(sprintf("Service[%s] already registered.", $resolverId));
        }
        $this->container->setDefinition($resolverId, $resolver);

        $loader = new Definition(
            $this->container->getParameter('config.delegating_loader.class'),
            array(new Reference($resolverId))
        );
        $loader->setPublic(false);
        $delegatingLoaderId = $this->container->getParameter('config.delegating_loader.id');
        if ($this->container->hasDefinition($delegatingLoaderId)) {
            throw new \Exception(sprintf("Service[%s] already registered.", $delegatingLoaderId));
        }
        $this->container->setDefinition($delegatingLoaderId, $loader);
    }

    /**
     * Parses a service ID based on bundle name.
     *
     * @param string $name
     *
     * @return string
     */
    protected static function parseServiceId($name)
    {
        return Container::underscore(preg_replace('/Bundle$/', '', $name));
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
            $id = static::parseServiceId($className);
            if ($this->cacheId === $id) {
                throw new \Exception(
                    "Cache ID[{$this->cacheId}] and Service ID[{$id}] ".
                    "based Bundle name[{$className}] are duplicated"
                );
            }
        }
    }

    /**
     * Builds a cache service ID.
     *
     * @param string $suffix ex) "yahoo_japan_config_cache"
     *
     * @return string ex) "config.yahoo_japan_config_cache"
     */
    protected function buildId($suffix)
    {
        return implode('.', array_merge(array($this->cacheId), (array) $suffix));
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
    protected function buildConfigurationId(ConfigurationInterface $configuration)
    {
        $reflection = new \ReflectionClass($configuration);
        $configId   = Container::underscore(strtr($reflection->getName(), '\\', '_'));

        return $this->buildId(array('configuration', $configId));
    }

    /**
     * Finds a configuration by a resource.
     *
     * @param ResourceInterface $resource
     *
     * @return ConfigurationInterface
     */
    protected function findConfigurationByResource(ResourceInterface $resource)
    {
        return $resource->getConfiguration() ?: $this->getInitializedConfiguration();
    }

    /**
     * Gets a initialized configuration.
     *
     * Throws exception if not set when this method executed
     *
     * @return ConfigurationInterface
     */
    protected function getInitializedConfiguration()
    {
        if (is_null($this->configuration)) {
            throw new \Exception('The Configuration must be set.');
        }

        return $this->configuration;
    }

    /**
     * Adds a directory resource.
     *
     * @param DirectoryResource $dir
     *
     * @return Register
     */
    protected function addDirectory(DirectoryResource $dir)
    {
        $this->dirs[] = $dir;

        return $this;
    }

    /**
     * Adds a file resource.
     *
     * @param FileResource $file
     *
     * @return Register
     */
    protected function addFile(FileResource $file)
    {
        $this->files[] = $file;

        return $this;
    }
}
