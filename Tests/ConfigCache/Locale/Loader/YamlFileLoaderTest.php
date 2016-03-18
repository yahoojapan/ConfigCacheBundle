<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache\Locale\Loader;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\Loader\ArrayLoader;
use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\Loader\YamlFileLoader;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class YamlFileLoaderTest extends TestCase
{
    public function testSetLocale()
    {
        $transLoader  = $this->util->createInterfaceMock('Symfony\Component\Translation\Loader\LoaderInterface');
        $translator   = $this->getTranslator($transLoader);
        $loader       = new YamlFileLoader();
        $arrayLoader1 = new ArrayLoader($translator);
        $name         = 'YahooJapan\ConfigCacheBundle\ConfigCache\Loader\ArrayLoaderInterface';
        $arrayLoader2 = $this->util->createInterfaceMock($name);
        $arrayLoader3 = new ArrayLoader($translator);
        $loader->addLoaders(array($arrayLoader1, $arrayLoader2, $arrayLoader3));

        $loader->setLocale('ja');

        $loaders = $this->util->getProperty($loader, 'loaders');
        if (isset($loaders[0]) && isset($loaders[1]) && isset($loaders[2])) {
            // the same added loaders
            $this->assertSame($arrayLoader1, $loaders[0]);
            $this->assertSame($arrayLoader2, $loaders[1]);
            $this->assertSame($arrayLoader3, $loaders[2]);

            // the same locale
            $this->assertSame('ja', $this->util->getProperty($loaders[0], 'locale'));

            // nothing to be set $loaders[1] because of not TranslationLoader

            $this->assertSame('ja', $this->util->getProperty($loaders[2], 'locale'));
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
        $container = $this->util->createInterfaceMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container
            ->expects($this->any())
            ->method('get')
            ->willReturn($loader)
            ;

        return $container;
    }
}
