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
        $this->factory->setContainer($serviceRegister->getContainer());
        $this->assertEquals($serviceRegister, $this->factory->createServiceRegister());
    }

    /**
     * @dataProvider createResourceRegisterProvider
     */
    public function testCreateFileRegister($setContainer, $expectedException)
    {
        if ($expectedException) {
            $this->setExpectedException($expectedException);
        }
        if ($setContainer) {
            $this->factory->setContainer($this->createContainerBuilderMock());
        }
        $serviceRegister = $this->createServiceRegister();
        $this->assertEquals(new FileRegister($serviceRegister), $this->factory->createFileRegister());
    }

    /**
     * createFileRegister, createDirectoryRegister shared
     *
     * @return array($setContainer, $expectedException)
     */
    public function createResourceRegisterProvider()
    {
        return array(
            // normal
            array(true, null),
            // exception(container is not set)
            array(false, '\RuntimeException'),
        );
    }

    /**
     * @dataProvider createResourceRegisterProvider
     */
    public function testCreateDirectoryRegister($setContainer, $expectedException)
    {
        if ($expectedException) {
            $this->setExpectedException($expectedException);
        }
        if ($setContainer) {
            $this->factory->setContainer($this->createContainerBuilderMock());
        }
        $serviceRegister = $this->createServiceRegister();
        $this->assertEquals(new DirectoryRegister($serviceRegister), $this->factory->createDirectoryRegister());
    }

    protected function createServiceRegister()
    {
        $container = $this->createContainerBuilderMock();
        $builder   = $this->factory->createIdBuilder();
        $configuration = $this->factory->createConfigurationRegister();

        return new ServiceRegister($container, $builder, $configuration);
    }

    protected function createContainerBuilderMock()
    {
        return $this->util->createMock('Symfony\Component\DependencyInjection\ContainerBuilder');
    }
}
