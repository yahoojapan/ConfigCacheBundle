<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache\Register;

use YahooJapan\ConfigCacheBundle\ConfigCache\Register\ConfigurationRegister;
use YahooJapan\ConfigCacheBundle\ConfigCache\Register\DirectoryRegister;
use YahooJapan\ConfigCacheBundle\ConfigCache\Register\FileRegister;
use YahooJapan\ConfigCacheBundle\ConfigCache\Register\RegisterFactory;
use YahooJapan\ConfigCacheBundle\ConfigCache\Register\ServiceIdBuilder;
use YahooJapan\ConfigCacheBundle\ConfigCache\Register\ServiceRegister;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class RegisterFactoryTest extends TestCase
{
    protected $factory;

    protected function setUp()
    {
        parent::setUp();

        $this->factory = new RegisterFactory();
    }

    public function testCreateIdBuilder()
    {
        $this->assertEquals(new ServiceIdBuilder(), $this->factory->createIdBuilder());
    }

    public function testCreateConfigurationRegister()
    {
        $this->assertEquals(new ConfigurationRegister(), $this->factory->createConfigurationRegister());
    }

    public function testCreateServiceRegister()
    {
        $serviceRegister = $this->createServiceRegister();
        $this->assertEquals($serviceRegister, $this->factory->createServiceRegister($serviceRegister->getContainer()));
    }

    public function testCreateFileRegister()
    {
        // not created ServiceRegister by factory (need the ContainerBuilder)
        $serviceRegister = $this->createServiceRegister();
        $container       = $serviceRegister->getContainer();
        $this->assertEquals(new FileRegister($serviceRegister), $this->factory->createFileRegister($container));

        // already created ServiceRegister (not need the ContainerBuilder)
        $serviceRegister = $this->factory->createServiceRegister($container);
        $this->assertEquals(new FileRegister($serviceRegister), $this->factory->createFileRegister());
    }

    public function testCreateDirectoryRegister()
    {
        // not created ServiceRegister by factory (need the ContainerBuilder)
        $serviceRegister = $this->createServiceRegister();
        $container       = $serviceRegister->getContainer();
        $this->assertEquals(new DirectoryRegister($serviceRegister), $this->factory->createDirectoryRegister($container));

        // already created ServiceRegister (not need the ContainerBuilder)
        $serviceRegister = $this->factory->createServiceRegister($container);
        $this->assertEquals(new DirectoryRegister($serviceRegister), $this->factory->createDirectoryRegister());
    }

    protected function createServiceRegister()
    {
        $container = $this->util->createMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $builder   = $this->factory->createIdBuilder();
        $configuration = $this->factory->createConfigurationRegister();

        return new ServiceRegister($container, $builder, $configuration);
    }
}
