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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\Register\ServiceRegister;
use YahooJapan\ConfigCacheBundle\ConfigCache\Register\ConfigurationRegister;
use YahooJapan\ConfigCacheBundle\ConfigCache\Register\ServiceIdBuilder;
use YahooJapan\ConfigCacheBundle\Tests\ConfigCache\RegisterTestCase;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\RegisterConfiguration;

class ServiceRegisterTest extends RegisterTestCase
{
    protected $registerClass    = 'YahooJapan\ConfigCacheBundle\ConfigCache\Locale\RegisterLocale';
    protected $configCacheClass = 'YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCache';

    /**
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
        $this->assertSame('setConfiguration', $actualCalls[1][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $actualCalls[1][1][0]);
        $this->assertSame('setDefaultLocale', $actualCalls[2][0]);
        $this->assertSame($container->getParameter('kernel.default_locale'), $actualCalls[2][1][0]);
        $this->assertSame('setLoader', $actualCalls[3][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $actualCalls[3][1][0]);
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
        $this->assertSame('setDefaultLocale', $actualCalls[1][0]);
        $this->assertSame($container->getParameter('kernel.default_locale'), $actualCalls[1][1][0]);
        $this->assertSame('setLoader', $actualCalls[2][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $actualCalls[2][1][0]);
    }

    public function testRegisterLocaleMethods()
    {
        $id = 'test_id';
        $definition = new Definition();
        list($register, $container) = $this->createRegisterMockAndContainer();
        $container->setDefinition($id, $definition);

        $serviceRegister = $this->util->getProperty($register, 'serviceRegister');
        $this->util->invoke($serviceRegister, 'registerLocaleMethods', $id);

        $calls = $definition->getMethodCalls();
        $this->assertSame('setDefaultLocale', $calls[0][0]);
        $this->assertSame($container->getParameter('kernel.default_locale'), $calls[0][1][0]);
        $this->assertSame('setLoader', $calls[1][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $calls[1][1][0]);
    }

    /**
     * assert getConfigCacheClass() override
     */
    public function testCreateCacheDefinition()
    {
        list($register, ) = $this->createRegisterMockAndContainer();
        $id = 'register_test';
        $serviceRegister = $this->util->getProperty($register, 'serviceRegister');

        // $register->createCacheDefinition()
        $register->setConfiguration(new RegisterConfiguration());
        $serviceRegister->setAppConfig(array('aaa' => 'bbb'));
        $this->util->getProperty($register, 'idBuilder')->setBundleId($id);
        $definition = $this->util->invoke($serviceRegister, 'createCacheDefinition');
        // assert(ConfigCache)
        $this->assertSame('YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCache', $definition->getClass());
    }

    /**
     * {@inheritdoc}
     */
    protected function createServiceRegister(
        ContainerBuilder $container,
        ServiceIdBuilder $idBuilder,
        ConfigurationRegister $configuration
    ) {
        return new ServiceRegister($container, $idBuilder, $configuration);
    }

}
