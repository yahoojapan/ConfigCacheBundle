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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * This is an abstract class for preprocessing RegisterTest, Locale\RegisterLocaleTest
 */
abstract class RegisterTestCase extends \PHPUnit_Framework_TestCase
{
    protected $cacheId;
    protected $registerClass = 'YahooJapan\ConfigCacheBundle\ConfigCache\Register';

    protected function getCacheId()
    {
        if (is_null($this->cacheId)) {
            $register = $this->getRegisterMock();
            $reflection = new \ReflectionProperty($register, 'cacheId');
            $reflection->setAccessible(true);
            $this->cacheId = $reflection->getValue($register);
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
     * return Register mock and container property
     */
    protected function getRegisterMockAndContainer(array $methods = array())
    {
        $container = $this->getContainerBuilder();
        $register  = $this->getRegisterMock($methods);
        $property  = new \ReflectionProperty($register, 'container');
        $property->setAccessible(true);
        $property->setValue($register, $container);

        return array($register, $property);
    }

    /**
     * return Register mock and container with parameters
     */
    protected function getRegisterMockAndContainerWithParameter(array $methods = array())
    {
        list($register, $container) = $this->getRegisterMockAndContainer($methods);
        $method = new \ReflectionMethod($register, 'setParameter');
        $method->setAccessible(true);
        $method->invoke($register);

        return array($register, $container);
    }

    protected function getContainerBuilder(array $data = array())
    {
        return new ContainerBuilder(new ParameterBag(array_merge(array(
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
    }
}
