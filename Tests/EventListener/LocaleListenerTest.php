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

use YahooJapan\ConfigCacheBundle\EventListener\LocaleListener;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class LocaleListenerTest extends TestCase
{
    /**
     * @dataProvider onKernelRequestProvider
     */
    public function testOnKernelRequest($isMasterRequest, $configCount)
    {
        $locale  = 'uk';
        $request = $this->util->createMock('Symfony\Component\HttpFoundation\Request', array('getLocale'));
        $request
            ->expects($isMasterRequest ? $this->once() : $this->never())
            ->method('getLocale')
            ->willReturn($locale)
            ;
        $name  = 'Symfony\Component\HttpKernel\Event\GetResponseEvent';
        $event = $this->util->createMock($name, array('isMasterRequest', 'getRequest'));
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
        $listener = new LocaleListener();
        for ($i = 0; $i < $configCount; $i++) {
            $name   = 'YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCacheInterface';
            $config = $this->util->createInterfaceMock($name);
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
        $listener = new LocaleListener();

        // state initialization
        $this->assertSame(array(), $this->util->getProperty($listener, 'configs'));

        // after added
        $name   = 'YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCacheInterface';
        $config = $this->util->createInterfaceMock($name);
        $listener->addConfig($config);
        $this->assertSame(array($config), $this->util->getProperty($listener, 'configs'));
    }
}
