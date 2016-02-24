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

use YahooJapan\ConfigCacheBundle\ConfigCache\Loader\XmlFileLoader;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class XmlFileLoaderTest extends TestCase
{
    protected static $loader;

    public static function setUpBeforeClass()
    {
        self::$loader = new XmlFileLoader();
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
            array(__DIR__.'/../../Fixtures/test_loader.xml', true),
            // not string
            array(new \StdClass(), false),
            // not xml extension
            array(__FILE__, false),
        );
    }

    /**
     * @dataProvider loadFileProvider
     */
    public function testLoadFile($file, $expected)
    {
        if ($expected === '\Exception') {
            $this->setExpectedException($expected);
        }
        $this->assertInstanceOf($expected, $this->util->invoke(self::$loader, 'loadFile', $file));
    }

    /**
     * @return array ($file, $expected)
     */
    public function loadFileProvider()
    {
        return array(
            // normal
            array(__DIR__.'/../../Fixtures/test_loader.xml', '\SimpleXMLElement'),
            // not exist file
            array(__DIR__.'/../../Fixtures/no_exists.xml', '\Exception'),
            // failed parse
            array(__DIR__.'/../../Fixtures/invalid_format.xml', '\Exception'),
        );
    }
}
