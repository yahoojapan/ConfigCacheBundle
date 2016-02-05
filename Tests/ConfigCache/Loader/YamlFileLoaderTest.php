<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache\Loader;

use YahooJapan\ConfigCacheBundle\ConfigCache\Loader\YamlFileLoader;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\ArrayLoader;

class YamlFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    protected static $loader;

    public static function setUpBeforeClass()
    {
        self::$loader = new YamlFileLoader();
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports($resource, $expected)
    {
        $this->assertSame($expected, self::$loader->supports($resource));
    }

    /**
     * @return array ($resource, expected)
     */
    public function supportsProvider()
    {
        return array(
            // normal
            array(__DIR__.'/../../Fixtures/test_service1.yml', true),
            // not string
            array(new \StdClass(), false),
            // not yml extension
            array(__FILE__, false),
        );
    }

    /**
     * @dataProvider loadFileProvider
     */
    public function testLoadFile($file, $expected)
    {
        $method = new \ReflectionMethod(self::$loader, 'loadFile');
        $method->setAccessible(true);
        if (is_string($expected) && class_exists($expected)) {
            $this->setExpectedException($expected);
        }
        $loaded = $method->invoke(self::$loader, $file);
        $this->assertSame($expected, $loaded);
    }

    /**
     * @return array ($file, $expected)
     */
    public function loadFileProvider()
    {
        return array(
            // normal
            array(
                __DIR__.'/../../Fixtures/test_service1.yml',
                array(
                    'test_service' => array(
                        'aaa' => 'bbb',
                        'ccc' => 'ddd',
                    ),
                ),
            ),
            // not local stream
            array('http://localhost', '\InvalidArgumentException'),
            // not exist file
            array(__DIR__.'/../../Fixtures/no_exists.yml', '\InvalidArgumentException'),
            // failed parse
            array(__DIR__.'/../../Fixtures/invalid_format.yml', '\Exception'),
        );
    }

    /**
     * @dataProvider loadFileWithArrayLoaderProvider
     */
    public function testLoadFileWithArrayLoader($file, $expected)
    {
        self::$loader->addLoader(new ArrayLoader());
        $method = new \ReflectionMethod(self::$loader, 'loadFile');
        $method->setAccessible(true);
        if (is_string($expected) && class_exists($expected)) {
            $this->setExpectedException($expected);
        }
        $loaded = $method->invoke(self::$loader, $file);
        $this->assertSame($expected, $loaded);
    }

    /**
     * @return array ($file, $expected)
     */
    public function loadFileWithArrayLoaderProvider()
    {
        return array(
            array(
                __DIR__.'/../../Fixtures/test_service_replace.yml',
                array(
                    'test_service' => array(
                        'aaa' => 'bbb',
                        'ccc' => 'replaced_value',
                        'zzz' => 'www',
                        'xxx' => 'yyy',
                    ),
                ),
            ),
            array(
                __DIR__.'/../../Fixtures/no_exists.yml',
                '\InvalidArgumentException',
            ),
        );
    }
}
