<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache\Locale\Register;

use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\Register\RegisterFactory;
use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\Register\ServiceRegister;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class RegisterFactoryTest extends TestCase
{
    protected $factory;

    protected function setUp()
    {
        parent::setUp();

        $this->factory = new RegisterFactory();
    }

    public function testCreateServiceRegister()
    {
        $serviceRegister = $this->createServiceRegister();
        $this->factory->setContainer($serviceRegister->getContainer());
        $this->assertEquals($serviceRegister, $this->factory->createServiceRegister());
    }

    protected function createServiceRegister()
    {
        $container = $this->util->createMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $builder   = $this->factory->createIdBuilder();
        $configuration = $this->factory->createConfigurationRegister();

        return new ServiceRegister($container, $builder, $configuration);
    }
}
