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

use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCache;
use YahooJapan\ConfigCacheBundle\Tests\ConfigCache\RegisterTestCase;

class RegisterLocaleTest extends RegisterTestCase
{
    protected $registerClass = 'YahooJapan\ConfigCacheBundle\ConfigCache\Locale\RegisterLocale';

    public function testRegister()
    {
        $register        = $this->createRegisterMock(array('initializeResources', 'registerInternal'));
        $serviceRegister = $this->createServiceRegisterMock(array('setTag'));
        $this->util->setProperty($register, 'serviceRegister', $serviceRegister);

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
        $serviceRegister
            ->expects($this->once())
            ->method('setTag')
            ->with(ConfigCache::TAG_LOCALE)
            ->willReturnSelf()
            ;
        $register->register();
    }

    public function testRegisterAll()
    {
        $register        = $this->createRegisterMock(array('initializeAllResources', 'registerInternal'));
        $serviceRegister = $this->createServiceRegisterMock(array('setTag'));
        $this->util
            ->setProperty($register, 'container', $this->getContainerBuilder())
            ->setProperty($register, 'serviceRegister', $serviceRegister)
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
        $serviceRegister
            ->expects($this->once())
            ->method('setTag')
            ->with(ConfigCache::TAG_LOCALE)
            ->willReturnSelf()
            ;
        $register->registerAll();
    }
}
