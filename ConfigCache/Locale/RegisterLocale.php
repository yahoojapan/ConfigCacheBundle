<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache\Locale;

use Symfony\Component\DependencyInjection\Reference;
use YahooJapan\ConfigCacheBundle\ConfigCache\Register;

/**
 * RegisterLocale registers a translated cache service by some bundles.
 */
class RegisterLocale extends Register
{
    protected $loaderId = 'yahoo_japan_config_cache.yaml_file_loader';

    /**
     * Registers a service by a bundle with locale.
     *
     * Registers Locale\ConfigCache services, and tags to set locale with ConfigCache LocaleListener.
     */
    public function register()
    {
        $this
            ->setTag(ConfigCache::TAG_LOCALE)
            ->initializeResources()
            ->registerInternal()
            ;
    }

    /**
     * Registers a service by all bundles with locale.
     */
    public function registerAll()
    {
        $this
            ->setTag(ConfigCache::TAG_LOCALE)
            ->initializeAllResources($this->container->getParameter('kernel.bundles'))
            ->registerInternal()
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function setCacheDefinition()
    {
        parent::setCacheDefinition();

        $this->addLocaleMethods($this->buildId($this->bundleId));
    }

    /**
     * {@inheritdoc}
     */
    protected function setCacheDefinitionByAlias($alias)
    {
        parent::setCacheDefinitionByAlias($alias);

        $this->addLocaleMethods($this->buildId(array($this->bundleId, $alias)));
    }

    /**
     * {@inheritdoc}
     */
    protected function createCacheDefinition()
    {
        $definition = parent::createCacheDefinition();
        $definition->setClass('YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCache');

        return $definition;
    }

    /**
     * Adds a locale addMethodCall to a definition.
     *
     * @param string $id
     */
    protected function addLocaleMethods($id)
    {
        $this->container->getDefinition($id)
            ->addMethodCall('setDefaultLocale', array($this->container->getParameter('kernel.default_locale')))
            ->addMethodCall('setLoader', array(new Reference($this->loaderId)))
            ;
    }
}
