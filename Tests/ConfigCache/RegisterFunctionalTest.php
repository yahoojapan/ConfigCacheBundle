<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache;

use YahooJapan\ConfigCacheBundle\Tests\Functional\KernelTestCase;

/**
 * Register functional tests.
 */
class RegisterFunctionalTest extends KernelTestCase
{
    protected function tearDown()
    {
        parent::tearDown();

        $this->deleteTmpDir();
    }

    public function testRegister()
    {
        $cache    = static::$kernel->getContainer()->get('config.register.sample');
        $expected = array(
            'invoice' => 34843,
            'date'    => '2001-01-23',
            'bill-to' => array(
                'given'  => 'Chris',
                'family' => 'Dumars',
            ),
        );
        $this->assertSame($expected, $cache->findAll());
    }

    public function testRegisterAll()
    {
        $cache    = static::$kernel->getContainer()->get('config.register_all');
        $expected = array(
            'invoice' => 34843,
            'date'    => '2001-01-23',
            'bill_to' => array(
                'given'  => 'Chris',
                'family' => 'Dumars',
            ),
            'ship_to' => array(
                'given'  => 'Taro',
                'family' => 'Yahoo',
            ),
        );
        $this->assertSame($expected, $cache->findAll());
    }
}
