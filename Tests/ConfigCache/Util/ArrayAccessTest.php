<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache\Util;

use YahooJapan\ConfigCacheBundle\ConfigCache\Util\ArrayAccess;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class ArrayAccessTest extends TestCase
{
    public function testConstruct()
    {
        // has no arguments
        $arrayAccess = new ArrayAccess();
        $this->assertSame(array(), $this->util->getProperty($arrayAccess, 'parameters'));
        // has arguments
        $parameters  = array('aaa' => 'bbb');
        $arrayAccess = new ArrayAccess($parameters);
        $this->assertSame($parameters, $this->util->getProperty($arrayAccess, 'parameters'));
    }

    /**
     * @dataProvider getProvider
     */
    public function testGet($parameters, $useDefault, $default, $path, $expected)
    {
        $arrayAccess = new ArrayAccess($parameters);
        $actual = $useDefault ? $arrayAccess->get($path, $default) : $arrayAccess->get($path);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return array($parameters, $useDefault, $default, $path, $expected)
     */
    public function getProvider()
    {
        $parameters = array(
            'depth1-1' => array(
                'depth2-1' => 'aaa',
            ),
            'depth1-2' => 'ddd',
            'depth1-3' => array(
                'depth2-2' => array(
                    'depth3-1' => 'bbb',
                ),
            ),
            'depth1-4' => array(
                'depth2-3' => array(
                    'depth3-2' => 'abcde',
                    'depth3-3' => '',
                    'depth3-4' => null,
                    'depth3-5' => true,
                    'depth3-6' => false,
                    'depth3-7' => 0.1,
                    //'depth3-8-1' => 0b0,
                    //'depth3-8-2' => "0b0",
                    'depth3-9-1' => 011,
                    'depth3-9-2' => "011",
                    'depth3-10-1' => 0x2,
                    'depth3-10-2' => "0x2",
                    'depth3-11-1' => 1e3,
                    'depth3-11-2' => "1e3",
                ),
            ),
            'depth1-5' => array(
                array('depth3-7' => 'ccc'),
                array('depth3-8' => 'ddd'),
                array('depth3-9' => 'eee'),
            ),
        );
        $default = array();

        return array(
            // normal, has no dot, result array
            array($parameters, false, null, 'depth1-1', array('depth2-1' => 'aaa')),
            // normal, has no dot, result string
            array($parameters, false, null, 'depth1-2', 'ddd'),
            // normal, has dot, result array
            array($parameters, false, null, 'depth1-3.depth2-2', array('depth3-1' => 'bbb')),
            // normal, has dot, result string (length >= 1)
            array($parameters, false, null, 'depth1-4.depth2-3.depth3-2', 'abcde'),
            // normal, has dot, result empty string
            array($parameters, false, null, 'depth1-4.depth2-3.depth3-3', ''),
            // normal, has dot, result null
            array($parameters, false, null, 'depth1-4.depth2-3.depth3-4', null),
            // normal, has dot, result true
            array($parameters, false, null, 'depth1-4.depth2-3.depth3-5', true),
            // normal, has dot, result false
            array($parameters, false, null, 'depth1-4.depth2-3.depth3-6', false),
            // normal, has dot, result 0 included
            array($parameters, false, null, 'depth1-5.0.depth3-7', 'ccc'),
            // normal, has dot, result except 0 integer index included
            array($parameters, false, null, 'depth1-5.2.depth3-9', 'eee'),
            // has no dot, no key
            array($parameters, false, null, 'aaa', $default),
            // has dot, has no key
            array($parameters, false, null, 'depth1-2.zzz.yyy.xxx', $default),
            // has dot, has no key, find except array (string with length >= 1)
            array($parameters, false, null, 'depth1-4.depth2-3.depth3-2.zzz', $default),
            // has dot, has no key, find except array (empty string)
            array($parameters, false, null, 'depth1-4.depth2-3.depth3-3.zzz', $default),
            // has dot, has no key, find except array (null)
            array($parameters, false, null, 'depth1-4.depth2-3.depth3-4.zzz', $default),
            // has dot, has no key, find except array (true)
            array($parameters, false, null, 'depth1-4.depth2-3.depth3-5.zzz', $default),
            // has dot, has no key, find except array (false)
            array($parameters, false, null, 'depth1-4.depth2-3.depth3-6.zzz', $default),
            // has dot, has no key, find except array (float)
            array($parameters, false, null, 'depth1-4.depth2-3.depth3-7.zzz', $default),
            // has dot, has no key, find except array (binary number) (> PHP5.4)
            //array($parameters, false, null, 'depth1-4.depth2-3.depth3-8-1.zzz', $default),
            //array($parameters, false, null, 'depth1-4.depth2-3.depth3-8-2.zzz', $default),
            // has dot, has no key, find except array (octal number)
            array($parameters, false, null, 'depth1-4.depth2-3.depth3-9-1.zzz', $default),
            array($parameters, false, null, 'depth1-4.depth2-3.depth3-9-2.zzz', $default),
            // has dot, has no key, find except array (hex number)
            array($parameters, false, null, 'depth1-4.depth2-3.depth3-10-1.zzz', $default),
            array($parameters, false, null, 'depth1-4.depth2-3.depth3-10-2.zzz', $default),
            // has dot, has no key, find except array (exponential)
            array($parameters, false, null, 'depth1-4.depth2-3.depth3-11-1.zzz', $default),
            array($parameters, false, null, 'depth1-4.depth2-3.depth3-11-2.zzz', $default),
            // specify default value
            array($parameters, true, $expectedDefault = null, 'aaa.0.bbb.1.ccc', $expectedDefault),
        );
    }

    public function testReplace()
    {
        $parameters  = array('aaa' => 'bbb');
        $arrayAccess = new ArrayAccess();
        $arrayAccess->replace($parameters);
        $this->assertSame($parameters, $this->util->getProperty($arrayAccess, 'parameters'));
    }

    public function testCreate()
    {
        $parameters = array('aaa' => 'bbb');
        $this->assertEquals(new ArrayAccess($parameters), ArrayAccess::create($parameters));
    }
}
