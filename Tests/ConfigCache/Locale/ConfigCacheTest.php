<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache\Locale;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageSelector;
use YahooJapan\ConfigCacheBundle\ConfigCache\Loader\YamlFileLoader;
use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\Loader\ArrayLoader;
use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\Loader\YamlFileLoader as YamlFileTranslatingLoader;
use YahooJapan\ConfigCacheBundle\Tests\ConfigCache\ConfigCacheTestCase;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\ConfigCacheConfiguration;

class ConfigCacheTest extends ConfigCacheTestCase
{
    protected static $configCache = 'YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCache';

    protected function reload()
    {
        parent::reload();
        self::$cache
            ->setDefaultLocale(null)
            ->setReferableLocales(array())
            // re-initialize here to operate loader in create()
            ->setLoader(new YamlFileLoader())
            ;
    }

    public function testConstruct()
    {
        $cache       = $this->util->createAbstractMock('Doctrine\Common\Cache\Cache');
        $loader      = $this->createLoaderMock();
        $className   = 'YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache';
        $configCache = $this->util->createMock($className);
        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();
        $this->assertNull($constructor->invoke($configCache, $cache, $loader));
        $this->assertNull($constructor->invoke($configCache, $cache, $loader, array('aaa' => 'bbb')));
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate($loader, $referableLocales, $resources, $expected)
    {
        // prepare
        $configuration = new ConfigCacheConfiguration();
        foreach ($resources as $resource) {
            self::$cache->addResource($resource, $configuration);
        }
        self::$cache
            ->setLoader($loader)
            ->setReferableLocales($referableLocales)
            ;

        // create()
        if (is_string($expected) && class_exists($expected)) {
            $this->setExpectedException($expected);
        }
        self::$cache->create();

        // has no referableLocales
        // no created directory for do-nothing
        if ($referableLocales === array()) {
            if (is_dir(self::$tmpDir)) {
                $this->fail('TmpDir is expected no creating.');
            } else {
                return;
            }
        }

        // has referableLocales
        $expectedCount = count($expected);
        foreach (array_keys($expected) as $fileName) {
            $hashFileName            = $this->getHashFileName($fileName);
            $expected[$hashFileName] = $expected[$fileName];
        }
        $finder = Finder::create()
            ->files()
            ->filter(function (\SplFileInfo $file) use ($expected) {
                if (isset($expected[$file->getFilename()])) {
                    return true;
                }

                return false;
            })
            ->in((array) self::$tmpDir)
            ;

        // assert extracted file count
        $this->assertSame($expectedCount, count($finder));
        // assert file content
        foreach ($finder as $file) {
            $data = require $file->getRealPath();
            $this->assertSame($expected[$file->getFilename()], $data['data']);
        }
    }

    /**
     * @return array ($loader, $referableLocales, $resources, $expected)
     */
    public function createProvider()
    {
        return array(
            // loader is not translation loader
            array(
                $this->createLoaderMock(),
                array(),
                array(__DIR__.'/../../Fixtures/test_service_trans.yml'),
                'Exception',
            ),
            // has no referableLocales
            array(
                $this->getTranslationLoader(),
                array(),
                array(__DIR__.'/../../Fixtures/test_service_trans.yml'),
                array(),
                array(),
            ),
            // has a referableLocale
            array(
                $this->getTranslationLoader(),
                array('ja'),
                array(__DIR__.'/../../Fixtures/test_service_trans.yml'),
                array('[cache.ja][1].php' => array(
                    'aaa' => 'bbb',
                    'ccc' => '文言1',
                    'zzz' => '文言2',
                    'xxx' => 'yyy',
                )),
            ),
            // has referableLocales greater than two
            array(
                $this->getTranslationLoader(),
                array('ja', 'en'),
                array(__DIR__.'/../../Fixtures/test_service_trans.yml'),
                array(
                    '[cache.ja][1].php' => array(
                        'aaa' => 'bbb',
                        'ccc' => '文言1',
                        'zzz' => '文言2',
                        'xxx' => 'yyy',
                    ),
                    '[cache.en][1].php' => array(
                        'aaa' => 'bbb',
                        'ccc' => 'message1',
                        'zzz' => 'message2',
                        'xxx' => 'yyy',
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider createInternalProvider
     */
    public function testCreateInternal(
        $locale,
        $defaultLocale,
        $resources,
        $expectedException,
        $expectedFileName,
        $expectedData
    ) {
        $configuration = new ConfigCacheConfiguration();
        foreach ($resources as $resource) {
            self::$cache->addResource($resource, $configuration);
        }

        self::$cache->setDefaultLocale($defaultLocale);
        $method = new \ReflectionMethod(self::$cache, 'createInternal');
        $method->setAccessible(true);
        if ($expectedException) {
            $this->setExpectedException('\Exception');
        }
        if (is_null($locale)) {
            $result = $method->invoke(self::$cache);
        } else {
            $result = $method->invoke(self::$cache, $locale);
        }

        // assert return
        $this->assertSame($expectedData, $result);

        // assert file created and the same require data
        $expectedFileNames = array($expectedFileName, $this->getHashFileName($expectedFileName));
        $finder = Finder::create()
            ->files()
            ->filter(function (\SplFileInfo $file) use ($expectedFileNames) {
                if (in_array($file->getFilename(), $expectedFileNames)) {
                    return true;
                }

                return false;
            })
            ->in((array) self::$tmpDir)
            ;
        $this->assertSame(1, count($finder));
        foreach ($finder as $file) {
            $data = require $file->getRealPath();
            $this->assertSame($expectedData, $data['data']);
        }
    }

    /**
     * @return array ($locale, $defaultLocale, $resources, $expectedException, $expectedFileName, $expectedData)
     */
    public function createInternalProvider()
    {
        return array(
            // has not locale, no default locale (exception)
            array(
                null,
                null,
                array(
                    __DIR__.'/../../Fixtures/test_service2.yml',
                ),
                true,
                null,
                null,
            ),
            // has no locale, has default locale
            array(
                null,
                'en',
                array(
                    __DIR__.'/../../Fixtures/test_service2.yml',
                ),
                false,
                // file name, data array
                '[cache.en][1].php',
                array(
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                ),
            ),
            // has locale
            array(
                'ja',
                'en',
                array(
                    __DIR__.'/../../Fixtures/test_service1.yml',
                    __DIR__.'/../../Fixtures/test_service2.yml',
                ),
                false,
                // file name, data array
                '[cache.ja][1].php',
                array(
                    'aaa' => 'bbb',
                    'ccc' => 'ddd',
                    'zzz' => 'www',
                    'xxx' => 'yyy',
                ),
            ),
        );
    }

    /**
     * @dataProvider getKeyProvider
     */
    public function testGetKey($locale, $currentLocale, $expectedException, $expected)
    {
        $method = new \ReflectionMethod(self::$cache, 'getKey');
        $method->setAccessible(true);
        self::$cache->setCurrentLocale($currentLocale);

        if ($expectedException) {
            $this->setExpectedException('\Exception');
        }
        $key = $method->invoke(self::$cache, $locale);

        $this->assertSame($expected, $key);
    }

    /**
     * @return array ($locale, $currentLocale, $expectedException, $expected)
     */
    public function getKeyProvider()
    {
        return array(
            // locale = null, currentLocale = null
            array(null, null, true, 'cache'),
            // locale = null, currentLocale != null
            array(null, 'ja', false, 'cache.ja'),
            // locale != null
            array('en', 'uk', false, 'cache.en'),
        );
    }

    public function testGetLocale()
    {
        self::$cache->setDefaultLocale('uk');
        $method = new \ReflectionMethod(self::$cache, 'getLocale');
        $method->setAccessible(true);

        // defaultLocale when has no currentLocale
        $this->assertSame('uk', $method->invoke(self::$cache));
        // currentLocale when has currentLocale
        self::$cache->setCurrentLocale('en');
        $this->assertSame('en', $method->invoke(self::$cache));
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
        $translator->addResource('loader', 'foo', 'ja');
        $translator->addResource('loader', 'foo', 'en');

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

    /**
     * loader inner translator
     */
    protected function getLoaderOnTranslator()
    {
        $loader = $this->util->createInterfaceMock('Symfony\Component\Translation\Loader\LoaderInterface');
        $loader
            ->expects($this->exactly(2))
            ->method('load')
            ->willReturnOnConsecutiveCalls(
                $this->getCatalogue('ja', array(
                    'transId1' => '文言1',
                    'transId2' => '文言2',
                )),
                $this->getCatalogue('en', array(
                    'transId1' => 'message1',
                    'transId2' => 'message2',
                ))
            )
            ;

        return $loader;
    }

    /**
     * loader to be set ConfigCache (inner ArrayLoader)
     */
    protected function getTranslationLoader()
    {
        $loader     = new YamlFileTranslatingLoader();
        $translator = $this->getTranslator($this->getLoaderOnTranslator());
        $loader->addLoader(new ArrayLoader($translator));

        return $loader;
    }

    protected function getCatalogue($locale, $messages)
    {
        $catalogue = new MessageCatalogue($locale);
        foreach ($messages as $key => $translation) {
            $catalogue->set($key, $translation);
        }

        return $catalogue;
    }

    protected function createLoaderMock()
    {
        return $this->findUtil()->createInterfaceMock('Symfony\Component\Config\Loader\LoaderInterface');
    }
}
