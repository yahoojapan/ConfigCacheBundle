<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\EventListener;

use YahooJapan\ConfigCacheBundle\EventListener\ConfigCacheListener;

class ConfigCacheListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider onKernelRequestProvider
     */
    public function testOnKernelRequest($isMasterRequest, $configCount)
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->setMethods(array('getLocale'))
            ->getMock()
            ;
        $locale = 'uk';
        $request
            ->expects($isMasterRequest ? $this->once() : $this->never())
            ->method('getLocale')
            ->willReturn($locale)
            ;
        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->setMethods(array('isMasterRequest', 'getRequest'))
            ->getMock()
            ;
        $event
            ->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn($isMasterRequest)
            ;
        $event
            ->expects($isMasterRequest ? $this->once() : $this->never())
            ->method('getRequest')
            ->willReturn($request)
            ;

        // test with new for nothing constructor
        $listener = new ConfigCacheListener();
        for ($i = 0; $i < $configCount; $i++) {
            $config = $this->getMockBuilder('YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCacheInterface')
                // mock only setCurrentLocale
                ->setMethods(array('setCurrentLocale', 'setReferableLocales', 'setDefaultLocale'))
                ->getMock()
                ;
            $config
                ->expects($this->once())
                ->method('setCurrentLocale')
                ->with($locale)
                ->willReturn(null)
                ;
            $listener->addConfig($config);
        }
        $listener->onKernelRequest($event);
    }

    /**
     * @return array($isMasterRequest, $configCount)
     */
    public function onKernelRequestProvider()
    {
        return array(
            // is not MasterRequest
            array(false, 0),
            // is MasterRequest, config zero
            array(true, 0),
            // is MasterRequest, config one
            array(true, 1),
            // is MasterRequest, config greater than two
            array(true, 2),
        );
    }

    public function testAddConfig()
    {
        $listener = new ConfigCacheListener();

        // state initialization
        $property = new \ReflectionProperty($listener, 'configs');
        $property->setAccessible(true);
        $this->assertSame(array(), $property->getValue($listener));

        // after added
        $config = $this->getMock('YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCacheInterface');
        $listener->addConfig($config);
        $this->assertSame(array($config), $property->getValue($listener));
    }
}