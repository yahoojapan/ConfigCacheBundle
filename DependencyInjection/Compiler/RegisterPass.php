<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache;
use YahooJapan\ConfigCacheBundle\ConfigCache\RestorablePhpFileCache;

/**
 * Registers ConfigCache services by services.yml.
 */
class RegisterPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds(ConfigCache::TAG_REGISTER) as $serviceId => $attributes) {
            $bundleName = $this->findBundleName($serviceId, $attributes);
            $extension  = $this->findExtension($container, $bundleName);
            $path       = $this->findPath($extension, $this->findResource($attributes));

            $container->addResource(new FileResource($path));
            // register doctrine cache
            $doctrineCacheId = $this->registerDoctrineCache($container, $serviceId, $bundleName);
            // register config cache
            $this->registerConfigCache($container, $serviceId, $doctrineCacheId, $path);
        }
    }

    /**
     * Finds a bundle name.
     *
     * If a bundle is Acme/DemoBundle, returns "acme_demo".
     *
     * @param string $serviceId
     * @param array  $attributes
     *
     * @return string
     */
    protected function findBundleName($serviceId, array $attributes)
    {
        if (isset($attributes[0]['bundle'])) {
            $bundleName = $attributes[0]['bundle'];
        } else {
            list($bundleName, ) = explode('.', $serviceId, 2) + array('', '');
        }

        return $bundleName;
    }

    /**
     * Finds an Extension based on a bundle name.
     *
     * @param ContainerBuilder $container
     * @param string           $bundleName
     *
     * @return ExtensionInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function findExtension(ContainerBuilder $container, $bundleName)
    {
        if (!$container->hasExtension($bundleName)) {
            throw new \InvalidArgumentException(sprintf(
                'The service ID prefix or the bundle attribute [%s] and your bundle name prefix must be identical.',
                $bundleName
            ));
        }

        return $container->getExtension($bundleName);
    }

    /**
     * Finds a file resource path in attributes.
     *
     * @param array $attributes
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function findResource(array $attributes)
    {
        if (!isset($attributes[0]['resource'])) {
            throw new \InvalidArgumentException('The resource attribute is required.');
        }

        return $attributes[0]['resource'];
    }

    /**
     * Finds a file path.
     *
     * @param ExtensionInterface $extension
     * @param string             $resource
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function findPath(ExtensionInterface $extension, $resource)
    {
        $class = new \ReflectionClass($extension);
        $path  = dirname($class->getFileName()).'/../Resources/config/'.$resource;
        if (!file_exists($path) && !file_exists($path = $resource)) {
            throw new \InvalidArgumentException(sprintf(
                'The file resource [%s] does not exist.',
                $resource
            ));
        }

        return $path;
    }

    /**
     * Registers a doctrine cache service and returns ID.
     *
     * @param ContainerBuilder $container
     * @param string           $serviceId
     * @param string           $bundleName
     *
     * @return string
     */
    protected function registerDoctrineCache(ContainerBuilder $container, $serviceId, $bundleName)
    {
        $restorableCacheIds = $container->findTaggedServiceIds(RestorablePhpFileCache::TAG_RESTORABLE_CACHE);
        if (array_key_exists($serviceId, $restorableCacheIds)) {
            $decoratingId = 'yahoo_japan_config_cache.restorable_php_file_cache';
        } else {
            $decoratingId = 'yahoo_japan_config_cache.php_file_cache';
        }
        $doctrineCache = new DefinitionDecorator($decoratingId);
        $doctrineCache->replaceArgument(0, $container->getParameter('kernel.cache_dir')."/{$bundleName}");
        $doctrineCacheId = "yahoo_japan_config_cache.doctrine.cache.{$serviceId}";
        $container->setDefinition($doctrineCacheId, $doctrineCache);

        return $doctrineCacheId;
    }

    /**
     * Registers a ConfigCache service.
     *
     * @param ContainerBuilder $container
     * @param string           $serviceId
     * @param string           $doctrineCacheId
     * @param string           $path
     */
    protected function registerConfigCache(ContainerBuilder $container, $serviceId, $doctrineCacheId, $path)
    {
        $container->findDefinition($serviceId)
            ->setArguments(array(
                new Reference($doctrineCacheId),
                new Reference('yahoo_japan_config_cache.delegating_loader'),
            ))
            ->addMethodCall('setArrayAccess', array(new Reference('yahoo_japan_config_cache.array_access')))
            ->addMethodCall('addResource', array($path))
            ->addMethodCall('setStrict', array(false))
            ->addMethodCall('setId', array($serviceId))
            ->addTag(ConfigCache::TAG_CACHE_WARMER)
            ;
    }
}
