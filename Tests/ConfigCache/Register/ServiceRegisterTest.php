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

use YahooJapan\ConfigCacheBundle\Tests\ConfigCache\RegisterTestCase;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\RegisterConfiguration;

class ServiceRegisterTest extends RegisterTestCase
{
    /**
     * Tests registerConfigCache and createCacheDefinition in this method
     *
     * @dataProvider registerConfigCacheProvider
     */
    public function testRegisterConfigCache($tag)
    {
        list($register, $container) = $this->createRegisterMockAndContainer();
        $id = 'register_test';
        $this->preSetCacheDefinition($register, $tag, $id);

        // differ by registerConfigCacheByAlias
        $register->setConfiguration($configuration = new RegisterConfiguration());
        // registerConfigCache
        $this->util->getProperty($register, 'serviceRegister')->registerConfigCache();

        // Definition
        $definition = $this->postSetCacheDefinition($container, $tag, $id);
        // DefinitionDecorator
        $parent     = $container->getDefinition($definition->getParent());

        // assert addMethodCalls simplified
        $actualCalls = array_merge($parent->getMethodCalls(), $definition->getMethodCalls());
        $this->assertSame('setArrayAccess', $actualCalls[0][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $actualCalls[0][1][0]);
        // differ by setCacheDefinitionByAlias
        $this->assertSame('setConfiguration', $actualCalls[1][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $actualCalls[1][1][0]);

        // assert(Configuration)
        $configId = $this->util->getProperty($register, 'idBuilder')->buildConfigurationId($configuration);
        $this->assertTrue($container->hasDefinition($configId));
        $definition = $container->getDefinition($configId);
        $this->assertFalse($definition->isPublic());
        $this->assertSame('YahooJapan\ConfigCacheBundle\Tests\Fixtures\RegisterConfiguration', $definition->getClass());
        $this->assertSame(0, count($definition->getArguments()));
    }

    /**
     * @return array ($tag)
     */
    public function registerConfigCacheProvider()
    {
        return array(
            // no tag
            array(null),
            // has tag
            array('test_tag'),
        );
    }

    /**
     * @dataProvider registerConfigCacheProvider
     */
    public function testRegisterConfigCacheByAlias($tag)
    {
        list($register, $container) = $this->createRegisterMockAndContainer();
        $id = 'register_test';
        $this->preSetCacheDefinition($register, $tag, $id);

        // registerConfigCacheByAlias
        $serviceRegister = $this->util->getProperty($register, 'serviceRegister');
        $this->util->invoke($serviceRegister, 'registerConfigCacheByAlias', $alias = 'test_alias');

        // Definition
        $definition = $this->postSetCacheDefinition($container, $tag, $id, $alias);
        // DefinitionDecorator
        $parent     = $container->getDefinition($definition->getParent());

        // assert addMethodCalls simplified
        $actualCalls = array_merge($parent->getMethodCalls(), $definition->getMethodCalls());
        $this->assertSame('setArrayAccess', $actualCalls[0][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $actualCalls[0][1][0]);
        $this->assertFalse(isset($actualCalls[1][0]));
        $this->assertFalse(isset($actualCalls[1][1][0]));
    }

    public function testRegisterConfiguration()
    {
        list($register, $container) = $this->createRegisterMockAndContainer();
        $configuration   = new RegisterConfiguration();
        $id              = 'register_test';
        $serviceRegister = $this->util->getProperty($register, 'serviceRegister');

        // state not registered ID
        $serviceRegister->registerConfiguration($id, $configuration);
        $this->assertTrue($container->hasDefinition($id));
        $definition = $container->getDefinition($id);
        $this->assertFalse($definition->isPublic());
        $this->assertSame('YahooJapan\ConfigCacheBundle\Tests\Fixtures\RegisterConfiguration', $definition->getClass());

        // state already registered ID
        $mock = $this->createConfigurationMock();
        $serviceRegister->registerConfiguration($id, $mock);
        $definition = $container->getDefinition($id);
        $this->assertSame('YahooJapan\ConfigCacheBundle\Tests\Fixtures\RegisterConfiguration', $definition->getClass());
        $this->assertFalse(strpos('Mock_ConfigurationInterface', $definition->getClass()) === 0);
    }

    protected function createConfigurationMock()
    {
        return $this->util->createInterfaceMock('Symfony\Component\Config\Definition\ConfigurationInterface');
    }
}
