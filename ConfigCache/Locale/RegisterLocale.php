<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
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
     * Registers Locale\ConfigCache services, and tags to set locale with ConfigCacheListener.
     */
    public function register()
    {
        $this->setTag(ConfigCache::TAG_LOCALE)->registerInternal();
    }

    /**
     * Registers a service by all bundles with locale.
     */
    public function registerAll()
    {
        $this->setTag(ConfigCache::TAG_LOCALE)->registerInternal('all');
    }

    /**
     * {@inheritdoc}
     */
    protected function setParameter()
    {
        parent::setParameter();

        // config.config_cache.class
        $this->container->setParameter(parent::getConfigCacheClass(), 'YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache');
        // config.locale.config_cache.class
        $this->container->setParameter($this->getConfigCacheClass(), 'YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCache');
    }

    /**
     * {@inheritdoc}
     */
    protected function setCacheDefinition()
    {
        $definition = $this->createCacheDefinition()
            ->addMethodCall('setDefaultLocale', array($this->container->getParameter('kernel.default_locale')))
            ->addMethodCall('setLoader', array(new Reference($this->loaderId)))
        ;
        $this->container->setDefinition($this->buildId($this->bundleId), $definition);
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfigCacheClass()
    {
        return $this->buildClassId('locale.config_cache');
    }
}
