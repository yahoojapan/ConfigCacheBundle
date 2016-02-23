<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * This is an abstract class for preprocessing RegisterTest, Locale\RegisterLocaleTest
 */
abstract class RegisterTestCase extends \PHPUnit_Framework_TestCase
{
    protected $cacheId;
    protected $registerClass    = 'YahooJapan\ConfigCacheBundle\ConfigCache\Register';
    protected $configCacheClass = 'YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache';

    /**
     * for testSetCacheDefinition, testSetCacheDefinitionByAlias
     */
    protected function preSetCacheDefinition($register, $tag, $id)
    {
        $this
            ->setProperty($register, 'bundleId', $id)
            ->setProperty($register, 'appConfig', array('aaa' => 'bbb'))
            ;
        if (!is_null($tag)) {
            $register->setTag($tag);
        }
    }

    /**
     * for testSetCacheDefinition, testSetCacheDefinitionByAlias
     */
    protected function postSetCacheDefinition($container, $tag, $id, $alias = '')
    {
        // assert(doctrine/cache)
        $doctrineCacheId = "{$this->getCacheId()}.doctrine.cache.{$id}";
        $this->assertTrue($container->hasDefinition($doctrineCacheId));
        // Definition
        $definition = $container->getDefinition($doctrineCacheId);
        // DefinitionDecorator
        $parent = $container->getDefinition($definition->getParent());
        $this->assertFalse($parent->isPublic());
        $this->assertSame('Doctrine\Common\Cache\PhpFileCache', $parent->getClass());
        $this->assertSame(
            $container->getParameter('kernel.cache_dir')."/{$id}",
            $definition->getArgument(0)
        );
        $this->assertSame('.php', $parent->getArgument(1));

        // assert(ConfigCache)
        $aliases = $alias !== '' ? array($alias) : array();
        $userCacheId = implode('.', array_merge(array($this->getCacheId(), $id), $aliases));
        $this->assertTrue($container->hasDefinition($userCacheId));
        $definition = $container->getDefinition($userCacheId);
        $this->assertTrue($definition->isPublic());
        $this->assertTrue($definition->isLazy());
        $this->assertSame($this->configCacheClass, $definition->getClass());
        $arguments = $definition->getArguments();
        $this->assertSame(3, count($arguments));
        foreach ($arguments as $i => $argument) {
            if ($i === 0) {
                $this->assertInstanceOf(
                    'Symfony\Component\DependencyInjection\Reference',
                    $argument,
                    'Unexpected argument "0" instance.'
                );
                $this->assertSame($doctrineCacheId, (string) $argument);
            } elseif ($i === 1) {
                $this->assertInstanceOf(
                    'Symfony\Component\DependencyInjection\Reference',
                    $argument,
                    'Unexpected argument "1" instance.'
                );
                $this->assertSame('yahoo_japan_config_cache.component.delegating_loader', (string) $argument);
            } elseif ($i === 2) {
                $this->assertSame(array('aaa' => 'bbb'), $argument);
            } else {
                $this->fail(sprintf('The ConfigCache argument "%s" is not set.', $i));
            }
        }
        if (!is_null($tag)) {
            $this->assertTrue($definition->hasTag($tag));
        } else {
            $this->assertSame(array(), $definition->getTags());
        }

        // addMethodCalls is asserted on test method

        return $definition;
    }

    protected function getCacheId()
    {
        if (is_null($this->cacheId)) {
            $this->cacheId = $this->getProperty($this->getRegisterMock(), 'cacheId');
        }

        return $this->cacheId;
    }

    protected function getRegisterMock(array $methods = array())
    {
        $register = $this->getMockBuilder($this->registerClass)
            ->disableOriginalConstructor()
            ->setMethods($methods ?: null)
            ->getMock()
            ;

        return $register;
    }

    /**
     * @return array list of Register mock, ContainerBuilder
     */
    protected function getRegisterMockAndContainer(array $methods = array())
    {
        $container = $this->getContainerBuilder();
        $register  = $this->getRegisterMock($methods);
        $property  = new \ReflectionProperty($register, 'container');
        $property->setAccessible(true);
        $property->setValue($register, $container);

        return array($register, $container);
    }

    protected function getContainerBuilder(array $data = array())
    {
        $container = new ContainerBuilder(new ParameterBag(array_merge(array(
            'kernel.bundles'        => array(
                'FrameworkBundle'             => 'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle',
                'YahooJapanConfigCacheBundle' => 'YahooJapan\\ConfigCacheBundle\\YahooJapanConfigCacheBundle',
            ),
            'kernel.cache_dir'      => __DIR__,
            'kernel.debug'          => false,
            'kernel.environment'    => 'test',
            'kernel.name'           => 'kernel',
            'kernel.root_dir'       => __DIR__,
            'kernel.default_locale' => 'ja',
        ), $data)));

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config'));
        $loader->load('pre_services.yml');

        return $container;
    }

    protected function getProperty($instance, $name)
    {
        $property = new \ReflectionProperty($instance, $name);
        $property->setAccessible(true);

        return $property->getValue($instance);
    }

    protected function setProperty($instance, $name, $value)
    {
        $property = new \ReflectionProperty($instance, $name);
        $property->setAccessible(true);
        $property->setValue($instance, $value);

        return $this;
    }
}
