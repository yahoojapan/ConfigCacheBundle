<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache\Locale\Register;

use Symfony\Component\DependencyInjection\Reference;
use YahooJapan\ConfigCacheBundle\ConfigCache\Register\ServiceRegister as BaseServiceRegister;

/**
 * ServiceRegister mainly registers services of the cache and the configuration by ContainerBuilder::setDefinition().
 */
class ServiceRegister extends BaseServiceRegister
{
    protected $loaderId = 'yahoo_japan_config_cache.locale.yaml_file_loader';

    /**
     * {@inheritdoc}
     */
    public function registerConfigCache()
    {
        parent::registerConfigCache();

        $this->registerLocaleMethods($this->idBuilder->buildCacheId());
    }

    /**
     * {@inheritdoc}
     */
    public function registerConfigCacheByAlias($alias)
    {
        parent::registerConfigCacheByAlias($alias);

        $this->registerLocaleMethods($this->idBuilder->buildCacheId(array($alias)));
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
     * Registers a locale addMethodCall to a definition.
     *
     * @param string $id
     */
    protected function registerLocaleMethods($id)
    {
        $this->container->getDefinition($id)
            ->addMethodCall('setDefaultLocale', array($this->container->getParameter('kernel.default_locale')))
            ->addMethodCall('setLoader', array(new Reference($this->loaderId)))
            ;
    }
}
