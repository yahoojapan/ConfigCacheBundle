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
    protected $container;
    protected $idBuilder;
    protected $configuration;
    protected $serviceRegister;
    protected $serviceRegisterClass = 'YahooJapan\ConfigCacheBundle\ConfigCache\Register\ServiceRegister';

    /**
     * Sets a ContainerBuilder.
     *
     * @param ContainerBuilder $container
     *
     * @return RegisterFactory
     */
    public function setContainer(ContainerBuilder $container)
    {
        $this->container = $container;

        return $this;
    }

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
     * @return ServiceRegister
     *
     * @throws \RuntimeException if $this->container is not set
     */
    public function createServiceRegister()
    {
        if (is_null($this->container)) {
            throw new \RuntimeException('ContainerBuilder must be set.');
        }
        $idBuilder     = $this->idBuilder ?: $this->createIdBuilder();
        $configuration = $this->configuration ?: $this->createConfigurationRegister();

        return $this->serviceRegister = new $this->serviceRegisterClass($this->container, $idBuilder, $configuration);
    }

    /**
     * Creates a FileRegister.
     *
     * @param ContainerBuilder $container
     *
     * @return FileRegister
     */
    public function createFileRegister()
    {
        return new FileRegister($this->serviceRegister ?: $this->createServiceRegister());
    }

    /**
     * Creates a DirectoryRegister.
     *
     * @param ContainerBuilder $container
     *
     * @return DirectoryRegister
     */
    public function createDirectoryRegister()
    {
        return new DirectoryRegister($this->serviceRegister ?: $this->createServiceRegister());
    }
}
