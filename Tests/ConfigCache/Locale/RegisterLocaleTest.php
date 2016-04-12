<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache\Locale;

use Symfony\Component\DependencyInjection\Definition;
use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCache;
use YahooJapan\ConfigCacheBundle\Tests\ConfigCache\RegisterTestCase;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\RegisterConfiguration;

class RegisterLocaleTest extends RegisterTestCase
{
    protected $registerClass    = 'YahooJapan\ConfigCacheBundle\ConfigCache\Locale\RegisterLocale';
    protected $configCacheClass = 'YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCache';

    public function testRegister()
    {
        $register = $this->createRegisterMock(array('setTag', 'initializeResources', 'registerInternal'));
        $register
            ->expects($this->once())
            ->method('setTag')
            ->with(ConfigCache::TAG_LOCALE)
            ->willReturnSelf()
            ;
        $register
            ->expects($this->once())
            ->method('initializeResources')
            ->willReturnSelf()
            ;
        $register
            ->expects($this->once())
            ->method('registerInternal')
            ->willReturn(null)
            ;
        $register->register();
    }

    public function testRegisterAll()
    {
        $methods = array('setTag', 'initializeAllResources', 'registerInternal');
        $register = $this->createRegisterMock($methods);
        $this->util->setProperty($register, 'container', $this->getContainerBuilder());
        $register
            ->expects($this->once())
            ->method('setTag')
            ->with(ConfigCache::TAG_LOCALE)
            ->willReturnSelf()
            ;
        $register
            ->expects($this->once())
            ->method('initializeAllResources')
            ->willReturnSelf()
            ;
        $register
            ->expects($this->once())
            ->method('registerInternal')
            ->willReturn(null)
            ;
        $register->registerAll();
    }

    /**
     * @dataProvider setCacheDefinitionProvider
     */
    public function testSetCacheDefinition($tag)
    {
        list($register, $container) = $this->createRegisterMockAndContainer();
        $id = 'register_test';
        $this->preSetCacheDefinition($register, $tag, $id);

        // differ by setCacheDefinitionByAlias
        $register->setConfiguration(new RegisterConfiguration());
        // setCacheDefinition
        $this->util->invoke($register, 'setCacheDefinition');

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
    public function setCacheDefinitionProvider()
    {
        return array(
            // no tag
            array(null),
            // has tag
            array('test_tag'),
        );
    }

    /**
     * @dataProvider setCacheDefinitionProvider
     */
    public function testSetCacheDefinitionByAlias($tag)
    {
        list($register, $container) = $this->createRegisterMockAndContainer();
        $id = 'register_test';
        $this->preSetCacheDefinition($register, $tag, $id);

        // setCacheDefinition
        $this->util->invoke($register, 'setCacheDefinitionByAlias', $alias = 'test_alias');

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

    public function testAddLocaleMethods()
    {
        $id = 'test_id';
        $definition = new Definition();
        list($register, $container) = $this->createRegisterMockAndContainer();
        $container->setDefinition($id, $definition);

        $this->util->invoke($register, 'addLocaleMethods', $id);

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
        // $register->createCacheDefinition()
        $register->setConfiguration(new RegisterConfiguration());
        $this->util->getProperty($register, 'idBuilder')->setBundleId($id);
        $definition = $this->util
            ->setProperty($register, 'appConfig', array('aaa' => 'bbb'))
            ->invoke($register, 'createCacheDefinition')
            ;
        // assert(ConfigCache)
        $this->assertSame('YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCache', $definition->getClass());
    }
}
