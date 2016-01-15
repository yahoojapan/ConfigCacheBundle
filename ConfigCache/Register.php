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
    protected $config;
    protected $configuration;
    protected $container;
    protected $resources;
    protected $excludes;
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
     * @param array              $config    An array of configuration values
     * @param ContainerBuilder   $container A ContainerBuilder instance
     * @param array              $resources Array of Resource key and Configuration instance value
     * @param array              $excludes  Exclude file names
     */
    public function __construct(
        ExtensionInterface $extension,
        array $config,
        ContainerBuilder $container,
        array $resources,
        array $excludes = array()
    ) {
        $this->extension = $extension;
        $this->config    = $config;
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
        $this->registerInternal();
    }

    /**
     * Registers a service by all bundles.
     */
    public function registerAll()
    {
        $this->registerInternal(true);
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
        $this->setParameter();
        $this->setLoaderDefinition();
    }

    /**
     * Register a service internal processing.
     *
     * @param bool $all all or one bundle(s)
     */
    protected function registerInternal($all = false)
    {
        $dirs    = array();
        $files   = array();

        if ($all) {
            list($dirs, $files) = $this->registerAllInternal($this->container->getParameter('kernel.bundles'));
        } else {
            list($dirs, $files) = $this->registerOneInternal();
        }

        // only if container has ConfigCache service Definition
        $cacheId = $this->buildId($this->bundleId);
        if ($this->container->hasDefinition($cacheId)) {
            $definition = $this->container->findDefinition($cacheId);

            if ($dirs) {
                foreach ($dirs as $resource) {
                    $this->container->addResource(new BaseDirectoryResource($resource->getResource()));

                    // private configuration definition, finally discarded because of private service
                    $privateId = $this->buildConfigurationId($this->findConfigurationByResource($resource));
                    $this->setConfigurationDefinition($privateId, $this->findConfigurationByResource($resource));

                    // find files under directories
                    $finder = $this->findFilesByDirectory($resource, $this->excludes);
                    foreach ($finder as $file) {
                        $definition->addMethodCall('addResource', array((string) $file, new Reference($privateId)));
                    }
                }
            }

            if ($files) {
                foreach ($files as $resource) {
                    $this->container->addResource(new BaseFileResource($resource->getResource()));

                    // private configuration definition, finally discarded because of private service
                    $privateId = $this->buildConfigurationId($this->findConfigurationByResource($resource));
                    $this->setConfigurationDefinition($privateId, $this->findConfigurationByResource($resource));

                    $definition->addMethodCall(
                        'addResource',
                        array((string) $resource->getResource(), new Reference($privateId))
                    );
                }
            }
        }
    }

    /**
     * Registers by a bundle internal processing.
     *
     * @return array list of $dirs, $files
     */
    protected function registerOneInternal()
    {
        $dirs  = array();
        $files = array();

        foreach ($this->resources as $resource) {
            if ($resource->exists()) {
                if ($resource instanceof DirectoryResource) {
                    $dirs[]  = $resource;
                } elseif ($resource instanceof FileResource) {
                    $files[] = $resource;
                }
            }
        }
        $this->setCacheDefinition();

        return array($dirs, $files);
    }

    /**
     * Registers by all bundles internal processing.
     *
     * @return array list of $dirs, $files
     */
    protected function registerAllInternal($bundles)
    {
        $dirs     = array();
        $files    = array();
        $register = false;

        foreach ($bundles as $fqcn) {
            $reflection = new \ReflectionClass($fqcn);
            foreach ($this->resources as $resource) {
                $path = dirname($reflection->getFilename()).$resource->getResource();
                if (is_dir($path)) {
                    $register = true;
                    $dirs[]   = new DirectoryResource($path, $this->findConfigurationByResource($resource));
                } elseif (file_exists($path)) {
                    $register = true;
                    $files[]  = new FileResource($path, $this->findConfigurationByResource($resource));
                }
            }
        }

        if ($register) {
            $this->setCacheDefinition();
        }

        return array($dirs, $files);
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
     * Sets class name parameters to the container.
     */
    protected function setParameter()
    {
        $container = $this->container;
        $container->setParameter($this->getPhpFileCacheClass(), 'Doctrine\Common\Cache\PhpFileCache');
        $container->setParameter($this->getConfigCacheClass(), 'YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache');
        $container->setParameter($this->getYamlFileLoaderClass(), 'YahooJapan\ConfigCacheBundle\ConfigCache\Loader\YamlFileLoader');
        $container->setParameter($this->getXmlFileLoaderClass(), 'YahooJapan\ConfigCacheBundle\ConfigCache\Loader\XmlFileLoader');
        $container->setParameter($this->getLoaderResolverClass(), 'Symfony\Component\Config\Loader\LoaderResolver');
        $container->setParameter($this->getDelegatingLoaderClass(), 'Symfony\Component\Config\Loader\DelegatingLoader');
    }

    /**
     * Sets a cache definition.
     */
    protected function setCacheDefinition()
    {
        $this->container->setDefinition($this->buildId($this->bundleId), $this->createCacheDefinition());
    }

    /**
     * Creates a cache definition for preparing setCacheDefinition.
     *
     * @return Definition
     */
    protected function createCacheDefinition()
    {
        // doctrine/cache
        $cache = new Definition(
            $this->container->getParameter($this->getPhpFileCacheClass()),
            array(
                $this->container->getParameter('kernel.cache_dir')."/{$this->bundleId}",
                '.php',
            )
        );
        $cache->setPublic(false);
        $cacheId = $this->buildId(array('doctrine', 'cache', $this->bundleId));
        $this->container->setDefinition($cacheId, $cache);

        // master configuration
        $configId = $this->buildConfigurationId($this->getInitializedConfiguration());
        $this->setConfigurationDefinition($configId, $this->getInitializedConfiguration());

        // ArrayAccess
        $arrayAccess = new Definition('YahooJapan\ConfigCacheBundle\ConfigCache\Util\ArrayAccess');
        $arrayAccess->setPublic(false);
        $arrayAccessId = $this->buildId(array('array_access', $this->bundleId));
        $this->container->setDefinition($arrayAccessId, $arrayAccess);

        // user cache
        $definition = new Definition(
            $this->container->getParameter($this->getConfigCacheClass()),
            array(
                new Reference($cacheId),
                new Reference($this->getDelegatingLoaderId()),
                $this->config,
            )
        );
        $definition->setLazy(true)
            ->addMethodCall('setConfiguration', array(new Reference($configId)))
            ->addMethodCall('setArrayAccess', array(new Reference($arrayAccessId)))
        ;
        if (!is_null($this->tag)) {
            $definition->addTag($this->tag);
        }

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
        $yamlLoader = new Definition($this->container->getParameter($this->getYamlFileLoaderClass()));
        $xmlLoader  = new Definition($this->container->getParameter($this->getXmlFileLoaderClass()));
        $yamlLoader->setPublic(false);
        $xmlLoader->setPublic(false);

        if ($this->container->hasDefinition($this->getYamlLoaderId())) {
            throw new \Exception(sprintf("Service[%s] already registered.", $this->getYamlLoaderId()));
        }
        if ($this->container->hasDefinition($this->getXmlLoaderId())) {
            throw new \Exception(sprintf("Service[%s] already registered.", $this->getXmlLoaderId()));
        }
        $this->container->setDefinition($this->getYamlLoaderId(), $yamlLoader);
        $this->container->setDefinition($this->getXmlLoaderId(), $xmlLoader);

        $resolver = new Definition(
            $this->container->getParameter($this->getLoaderResolverClass()),
            array(array(
                new Reference($this->getYamlLoaderId()),
                new Reference($this->getXmlLoaderId()),
            ))
        );
        $resolver->setPublic(false);
        if ($this->container->hasDefinition($this->getLoaderResolverId())) {
            throw new \Exception(sprintf("Service[%s] already registered.", $this->getLoaderResolverId()));
        }
        $this->container->setDefinition($this->getLoaderResolverId(), $resolver);

        $loader = new Definition(
            $this->container->getParameter($this->getDelegatingLoaderClass()),
            array(new Reference($this->getLoaderResolverId()))
        );
        $loader->setPublic(false);
        if ($this->container->hasDefinition($this->getDelegatingLoaderId())) {
            throw new \Exception(sprintf("Service[%s] already registered.", $this->getDelegatingLoaderId()));
        }
        $this->container->setDefinition($this->getDelegatingLoaderId(), $loader);
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
     * Builds a class parameter ID.
     *
     * @param string $suffix ex) "php_file_cache"
     *
     * @return string ex) "config.php_file_cache.class"
     */
    protected function buildClassId($suffix)
    {
        return $this->buildId(array_merge((array) $suffix, array('class')));
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
     * Gets a yaml loader service ID.
     *
     * @return string
     */
    protected function getYamlLoaderId()
    {
        return $this->buildId('yaml_file_loader');
    }

    /**
     * Gets a xml loader service ID.
     *
     * @return string
     */
    protected function getXmlLoaderId()
    {
        return $this->buildId('xml_file_loader');
    }

    /**
     * Gets a loader resolver service ID.
     *
     * @return string
     */
    protected function getLoaderResolverId()
    {
        return $this->buildId('loader_resolver');
    }

    /**
     * Gets a delegating loader service ID.
     *
     * @return string
     */
    protected function getDelegatingLoaderId()
    {
        return $this->buildId('delegating_loader');
    }

    /**
     * Gets a class parameter ID.
     *
     * @return string
     */
    protected function getPhpFileCacheClass()
    {
        return $this->buildClassId('php_file_cache');
    }

    /**
     * Gets a class parameter ID.
     *
     * @return string
     */
    protected function getConfigCacheClass()
    {
        return $this->buildClassId('config_cache');
    }

    /**
     * Gets a class parameter ID.
     *
     * @return string
     */
    protected function getYamlFileLoaderClass()
    {
        return $this->buildClassId('yaml_file_loader');
    }

    /**
     * Gets a class parameter ID.
     *
     * @return string
     */
    protected function getXmlFileLoaderClass()
    {
        return $this->buildClassId('xml_file_loader');
    }

    /**
     * Gets a class parameter ID.
     *
     * @return string
     */
    protected function getLoaderResolverClass()
    {
        return $this->buildClassId('loader_resolver');
    }

    /**
     * Gets a class parameter ID.
     *
     * @return string
     */
    protected function getDelegatingLoaderClass()
    {
        return $this->buildClassId('delegating_loader');
    }
}