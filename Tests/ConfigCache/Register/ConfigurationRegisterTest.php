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
use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\FileResource;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\RegisterConfiguration;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class ConfigurationRegisterTest extends TestCase
{
    public function testFind()
    {
        $mock     = $this->createConfigurationMock();
        $real     = new RegisterConfiguration();
        $register = new ConfigurationRegister();
        $register->setConfiguration($real);

        // enabled configuration setting on resource
        $resource = new FileResource(__DIR__.'/../Fixtures/test_service1.yml', $mock);
        $this->assertSame($mock, $register->find($resource));
        // disabled configuration setting on resource
        $resource = new FileResource(__DIR__.'/../Fixtures/test_service1.yml');
        $this->assertSame($real, $register->find($resource));
    }

    public function testFindInitialized()
    {
        $register = new ConfigurationRegister();

        // configuration not set
        $this->setExpectedException('\Exception');
        // throw \Exception
        $register->findInitialized();

        // configuration set
        $configuration = new RegisterConfiguration();
        $register->setConfiguration($configuration);
        $this->assertSame($configuration, $register->findInitialized());
    }

    protected function createConfigurationMock()
    {
        return $this->util->createInterfaceMock('Symfony\Component\Config\Definition\ConfigurationInterface');
    }
}
