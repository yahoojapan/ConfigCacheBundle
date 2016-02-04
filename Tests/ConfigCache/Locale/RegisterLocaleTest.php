<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
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
        $register = $this->getRegisterMock(array('setTag', 'initializeResources', 'registerInternal'));
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
        $register = $this->getRegisterMock($methods);
        $this->setProperty($register, 'container', $this->getContainerBuilder());
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
        list($register, $container) = $this->getRegisterMockAndContainerWithParameter();
        $id = 'register_test';
        $this->preSetCacheDefinition($register, $tag, $id);

        // differ by setCacheDefinitionByAlias
        $configuration = new RegisterConfiguration();
        $this->setProperty($register, 'configuration', $configuration);

        // setCacheDefinition
        $method = new \ReflectionMethod($register, 'setCacheDefinition');
        $method->setAccessible(true);
        $method->invoke($register);

        $definition = $this->postSetCacheDefinition($container, $register, $tag, $id);

        // assert addMethodCalls simplified
        $calls = $definition->getMethodCalls();
        $this->assertSame('setArrayAccess', $calls[0][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $calls[0][1][0]);
        $this->assertSame('setConfiguration', $calls[1][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $calls[1][1][0]);
        $this->assertSame('setDefaultLocale', $calls[2][0]);
        $this->assertSame($container->getValue($register)->getParameter('kernel.default_locale'), $calls[2][1][0]);
        $this->assertSame('setLoader', $calls[3][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $calls[3][1][0]);
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
        list($register, $container) = $this->getRegisterMockAndContainerWithParameter();
        $id = 'register_test';
        $this->preSetCacheDefinition($register, $tag, $id);

        // setCacheDefinition
        $alias  = 'test_alias';
        $method = new \ReflectionMethod($register, 'setCacheDefinitionByAlias');
        $method->setAccessible(true);
        $method->invoke($register, $alias);

        $definition = $this->postSetCacheDefinition($container, $register, $tag, $id, $alias);

        // assert addMethodCalls simplified
        $calls = $definition->getMethodCalls();
        $this->assertSame('setArrayAccess', $calls[0][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $calls[0][1][0]);
        $this->assertSame('setDefaultLocale', $calls[1][0]);
        $this->assertSame($container->getValue($register)->getParameter('kernel.default_locale'), $calls[1][1][0]);
        $this->assertSame('setLoader', $calls[2][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $calls[2][1][0]);
    }

    public function testAddLocaleMethods()
    {
        $id = 'test_id';
        $definition = new Definition();
        list($register, $container) = $this->getRegisterMockAndContainer();
        $container->getValue($register)->setDefinition($id, $definition);

        $method = new \ReflectionMethod($register, 'addLocaleMethods');
        $method->setAccessible(true);
        $method->invoke($register, $id);

        $calls = $definition->getMethodCalls();
        $this->assertSame('setDefaultLocale', $calls[0][0]);
        $this->assertSame($container->getValue($register)->getParameter('kernel.default_locale'), $calls[0][1][0]);
        $this->assertSame('setLoader', $calls[1][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $calls[1][1][0]);
    }

    /**
     * assert getConfigCacheClass() override
     */
    public function testCreateCacheDefinition()
    {
        list($register, ) = $this->getRegisterMockAndContainerWithParameter();
        $id = 'register_test';
        $this
            ->setProperty($register, 'bundleId', $id)
            ->setProperty($register, 'config', array('aaa' => 'bbb'))
            ->setProperty($register, 'configuration', new RegisterConfiguration())
            ;

        // $register->createCacheDefinition()
        $method = new \ReflectionMethod($register, 'createCacheDefinition');
        $method->setAccessible(true);
        $definition = $method->invoke($register);

        // assert(ConfigCache)
        $this->assertSame('YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCache', $definition->getClass());
    }
}
