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

use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\Loader\ArrayLoader;
use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\Loader\YamlFileLoader;

class YamlFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testSetLocale()
    {
        $translator   = $this->getTranslator($this->getMock('Symfony\Component\Translation\Loader\LoaderInterface'));
        $loader       = new YamlFileLoader();
        $arrayLoader1 = new ArrayLoader($translator);
        $arrayLoader2 = $this->getMock('YahooJapan\ConfigCacheBundle\ConfigCache\Loader\ArrayLoaderInterface');
        $arrayLoader3 = new ArrayLoader($translator);
        $loader->addLoaders(array($arrayLoader1, $arrayLoader2, $arrayLoader3));

        $loader->setLocale('ja');

        $property = new \ReflectionProperty($loader, 'loaders');
        $property->setAccessible(true);
        $loaders = $property->getValue($loader);
        if (isset($loaders[0]) && isset($loaders[1]) && isset($loaders[2])) {
            // the same added loaders
            $this->assertSame($arrayLoader1, $loaders[0]);
            $this->assertSame($arrayLoader2, $loaders[1]);
            $this->assertSame($arrayLoader3, $loaders[2]);

            // the same locale
            $property = new \ReflectionProperty($loaders[0], 'locale');
            $property->setAccessible(true);
            $this->assertSame('ja', $property->getValue($loaders[0]));

            // nothing to be set $loaders[1] because of not TranslationLoader

            $property = new \ReflectionProperty($loaders[2], 'locale');
            $property->setAccessible(true);
            $this->assertSame('ja', $property->getValue($loaders[2]));
        } else {
            $this->fail('Unexpected setLocale.');
        }
    }

    /**
     * @see Symfony\Bundle\FrameworkBundle\Tests\Translation\TranslatorTest
     */
    protected function getTranslator($loader)
    {
        $translator = new Translator(
            $this->getContainer($loader),
            new MessageSelector(),
            array('loader' => array('loader'))
        );
        // set default "ja" the same as normally created services
        $translator->setFallbackLocales(array('ja'));
        $translator->addResource('loader', 'foo', 'ja');
        $translator->addResource('loader', 'foo', 'en');
        $translator->addResource('loader', 'foo', 'it');

        return $translator;
    }

    protected function getContainer($loader)
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container
            ->expects($this->any())
            ->method('get')
            ->willReturn($loader)
            ;

        return $container;
    }
}
