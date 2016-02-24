<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache\Locale\Loader;

use Symfony\Component\Translation\MessageCatalogue;
use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\Loader\ArrayLoader;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class ArrayLoaderTest extends TestCase
{
    /**
     * @dataProvider walkInternalProvider
     */
    public function testWalkInternal($messages, $hasMessage, $value, $expected)
    {
        $catalogue  = new MessageCatalogue('en', $messages);
        $methods    = array('getCatalogue', 'trans');
        $translator = $this->util->createMock('Symfony\Bundle\FrameworkBundle\Translation\Translator', $methods);
        $translator
            ->expects($this->once())
            ->method('getCatalogue')
            ->willReturn($catalogue)
            ;
        $translator
            ->expects($hasMessage ? $this->once() : $this->never())
            ->method('trans')
            ->with($value)
            ->willReturn($expected)
            ;
        $loader = new ArrayLoader($translator);

        $method = new \ReflectionMethod($loader, 'walkInternal');
        $method->setAccessible(true);
        $method->invokeArgs($loader, array(&$value, 'nouse'));
        $this->assertSame($expected, $value);
    }

    /**
     * @return array($messages, $hasMessage, $value, $expected)
     */
    public function walkInternalProvider()
    {
        return array(
            // has message
            array(
                array(
                    'messages' => array(
                        $value = 'message_label' => $expected = 'translated',
                    ),
                ),
                true,
                $value,
                $expected,
            ),
            // has no message
            array(
                array(
                    'messages' => array(
                        'has_no_label' => 'translated',
                    ),
                ),
                false,
                $value,
                $value,
            ),
        );
    }

    public function testGetInternalMethod()
    {
        $loader = $this->util->createMock('YahooJapan\ConfigCacheBundle\ConfigCache\Locale\Loader\ArrayLoader');
        $method = new \ReflectionMethod($loader, 'getInternalMethod');
        $method->setAccessible(true);
        // has no locale
        $this->assertSame('walkInternal', $method->invoke($loader));
        // has locale
        $this->util->setProperty($loader, 'locale', 'en');
        $this->assertSame('walkByLocaleInternal', $method->invoke($loader));
    }
}
