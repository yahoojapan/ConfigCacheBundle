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

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * RegisterFactory creates register objects.
 */
class RegisterFactory
{
    protected $idBuilder;
    protected $configuration;
    protected $serviceRegister;

    /**
     * Creates a service ID builder.
     *
     * @return ServiceIdBuilder
     */
    public function createIdBuilder()
    {
        return $this->idBuilder = new ServiceIdBuilder();
    }

    /**
     * Creates a ConfigurationRegister.
     *
     * @return ConfigurationRegister
     */
    public function createConfigurationRegister()
    {
        return $this->configuration = new ConfigurationRegister();
    }

    /**
     * Creates a ServiceRegister.
     *
     * @param ContainerBuilder $container
     *
     * @return ServiceRegister
     */
    public function createServiceRegister(ContainerBuilder $container)
    {
        $idBuilder     = $this->idBuilder ?: $this->createIdBuilder();
        $configuration = $this->configuration ?: $this->createConfigurationRegister();

        return $this->serviceRegister = new ServiceRegister($container, $idBuilder, $configuration);
    }

    /**
     * Creates a FileRegister
     *
     * @param ContainerBuilder $container
     *
     * @return FileRegister
     */
    public function createFileRegister(ContainerBuilder $container = null)
    {
        return new FileRegister($this->serviceRegister ?: $this->createServiceRegister($container));
    }

    /**
     * Creates a DirectoryRegister
     *
     * @param ContainerBuilder $container
     *
     * @return DirectoryRegister
     */
    public function createDirectoryRegister(ContainerBuilder $container = null)
    {
        return new DirectoryRegister($this->serviceRegister ?: $this->createServiceRegister($container));
    }
}
