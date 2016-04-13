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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache;

/**
 * ServiceRegister mainly registers services of the cache and the configuration by ContainerBuilder::setDefinition().
 */
class ServiceRegister
{
    protected $container;
    protected $idBuilder;
    protected $configuration;
    // ConfigCache service tag
    protected $tag;
    protected $appConfig = array();

    /**
     * Constructor.
     *
     * @param ContainerBuilder      $container
     * @param ServiceIdBuilder      $idBuilder
     * @param ConfigurationRegister $configuration
     */
    public function __construct(
        ContainerBuilder      $container,
        ServiceIdBuilder      $idBuilder,
        ConfigurationRegister $configuration
    ) {
        $this->container     = $container;
        $this->idBuilder     = $idBuilder;
        $this->configuration = $configuration;
    }

    /**
     * Registers a ConfigCache service (definition).
     */
    public function registerConfigCache()
    {
        $id         = $this->idBuilder->buildCacheId();
        $definition = $this->createCacheDefinition();
        $this->registerConfigurationMethod($definition);
        $this->container->setDefinition($id, $definition);
    }

    /**
     * Registers a ConfigCache service (definition) by alias.
     *
     * @param string $alias
     */
    public function registerConfigCacheByAlias($alias)
    {
        $id         = $this->idBuilder->buildCacheId(array($alias));
        $definition = $this->createCacheDefinition();
        $this->container->setDefinition($id, $definition);
    }

    /**
     * Registers a Configuration service.
     *
     * @param string                 $configId
     * @param ConfigurationInterface $configuration
     */
    public function registerConfiguration($configId, ConfigurationInterface $configuration)
    {
        if (!$this->container->hasDefinition($configId)) {
            $reflection       = new \ReflectionClass($configuration);
            $configDefinition = new Definition($reflection->getName());
            $configDefinition->setPublic(false);
            $this->container->setDefinition($configId, $configDefinition);
        }
    }

    /**
     * Sets a config_cache service tag.
     *
     * @param string $tag
     *
     * @return ServiceRegister
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Sets an application config.
     *
     * @param array $appConfig
     *
     * @return ServiceRegister
     */
    public function setAppConfig(array $appConfig)
    {
        $this->appConfig = $appConfig;

        return $this;
    }

    /**
     * Registers a Configuration set method to Definition.
     *
     * @param Definition $definition
     *
     * @return Definition
     */
    protected function registerConfigurationMethod(Definition $definition)
    {
        // master configuration
        $configId = $this->idBuilder->buildConfigurationId($this->configuration->findInitialized());
        $this->registerConfiguration($configId, $this->configuration->findInitialized());
        $definition->addMethodCall('setConfiguration', array(new Reference($configId)));

        return $definition;
    }

    /**
     * Creates a cache definition without Configuration Reference.
     *
     * @return Definition
     */
    protected function createCacheDefinition()
    {
        // doctrine/cache
        $cache = new DefinitionDecorator('yahoo_japan_config_cache.php_file_cache');
        // only replace cache directory
        $bundleId = $this->idBuilder->getBundleId();
        $cache->replaceArgument(0, $this->container->getParameter('kernel.cache_dir')."/{$bundleId}");
        $cacheId = $this->idBuilder->buildId(array('doctrine', 'cache', $bundleId));
        $this->container->setDefinition($cacheId, $cache);

        // user cache
        $definition = new DefinitionDecorator('yahoo_japan_config_cache.config_cache');
        $definition
            ->setPublic(true)
            ->setArguments(array(
                new Reference($cacheId),
                new Reference('yahoo_japan_config_cache.delegating_loader'),
                $this->appConfig,
            ))
            ->addTag(ConfigCache::TAG_CACHE_WARMER)
            ;
        if (!is_null($this->tag)) {
            $definition->addTag($this->tag);
        }

        return $definition;
    }
}
