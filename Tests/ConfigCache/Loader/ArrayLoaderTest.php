<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache\Loader;

use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class ArrayLoaderTest extends TestCase
{
    protected $loader;

    public function setUp()
    {
        parent::setUp();

        // re-create each test case (if not, fail to run expects method)
        $this->loader = $this->createLoaderMock(array('walkAllLeaves', 'walkInternal'));
    }

    public function testLoad()
    {
        $data = array(
            'aaa' => 'bbb',
            'ccc' => 'ddd',
        );
        $this->loader
            ->expects($this->once())
            ->method('walkAllLeaves')
            ->willReturn(null)
            ;
        $this->assertSame($data, $this->loader->load($data));
    }

    /**
     * assert not changed value, and calling walkInternal
     */
    public function testWalkAllLeaves()
    {
        // mock only walkInternal
        $this->loader = $this->createLoaderMock(array('walkInternal'));
        // three leaves data, called walkInternal three times
        $data = array(
            'aaa' => 'bbb',
            'ccc' => 'prefix_ddd',
            array(
                'eee' => 'fff',
                'ggg' => 'prefix_hhh',
            ),
            'iii' => 'jjj',
        );
        $expected = $data;
        $this->loader
            ->expects($this->exactly(5))
            ->method('walkInternal')
            ->willReturn(null)
            ;
        $this->util->invokeArgs($this->loader, 'walkAllLeaves', array(&$data));
        $this->assertSame($expected, $data);
    }

    public function testWalkInternal()
    {
        // not use mock method
        $this->loader = $this->createLoaderMock();
        list($key, $value) = array('key', 'prefix_value');
        // use invokeArgs to avoid PHP Deprecated when pass by reference
        $this->util->invokeArgs($this->loader, 'walkInternal', array(&$value, $key));
        // not changed
        $this->assertSame('key', $key);
        $this->assertSame('prefix_value', $value);
    }

    public function testGetInternalMethod()
    {
        $this->assertSame('walkInternal', $this->util->invoke($this->loader, 'getInternalMethod'));
    }

    protected function createLoaderMock(array $methods = null)
    {
        return $this->util->createAbstractMock('YahooJapan\ConfigCacheBundle\ConfigCache\Loader\ArrayLoader', $methods);
    }
}
