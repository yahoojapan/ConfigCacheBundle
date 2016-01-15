<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use YahooJapan\ConfigCacheBundle\DependencyInjection\Configuration;
use YahooJapan\ConfigCacheBundle\DependencyInjection\YahooJapanConfigCacheExtension;

class YahooJapanConfigCacheExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider loadProvider
     */
    public function testLoad($configs, $hasLocale, $enabled, $expectedLocales, $expectedPriority, $expectedException)
    {
        $container     = new ContainerBuilder();
        $extension     = new YahooJapanConfigCacheExtension();
        $configuration = new Configuration();

        try {
            $extension->load($configs, $container);
        } catch (\Exception $e) {
            if ($expectedException) {
                return;
            }
            $this->fail('Unexpected exception occurred.');
        }

        $arrayLoaderId     = 'yahoo_japan_config_cache.array_loader';
        $yamlLoaderId      = 'yahoo_japan_config_cache.yaml_file_loader';
        $listenerId        = 'yahoo_japan_config_cache.config_cache_listener';
        $localesParameter  = 'yahoo_japan_config_cache.locales';
        $priorityParameter = 'yahoo_japan_config_cache.listener_priority';
        $this->assertTrue($container->hasDefinition($arrayLoaderId));
        $this->assertTrue($container->hasDefinition($yamlLoaderId));
        if ($hasLocale && $enabled) {
            $this->assertSame($expectedLocales, $container->getParameter($localesParameter));
            $this->assertTrue($container->hasDefinition($listenerId));
            $this->assertSame($expectedPriority, $container->getParameter($priorityParameter));
        } else {
            $this->assertFalse($container->hasParameter($localesParameter));
            $this->assertFalse($container->hasDefinition($listenerId));
            $this->assertFalse($container->hasParameter($priorityParameter));
        }
    }

    /**
     * @return array($configs, $hasLocale, $enabled, $expectedLocales, $expectedPriority, $expectedException)
     */
    public function loadProvider()
    {
        $locales = array('ja', 'en');
        $defaultPriority = 0;

        return array(
            // has no $config
            array(
                array(),
                false,
                false,
                null,
                null,
                false,
            ),
            // has $config['locale'], has no enabled
            array(
                array(
                    'yahoo_japan_config_cache' => array(
                        'locale' => array(),
                    ),
                ),
                true,
                false,
                null,
                null,
                false,
            ),
            // has $config['locale'], enabled = false
            array(
                array(
                    'yahoo_japan_config_cache' => array(
                        'locale' => array(
                            'enabled' => false,
                        ),
                    ),
                ),
                true,
                false,
                null,
                null,
                false,
            ),
            // has $config['locale'], enabled = true, has no locale (exception)
            array(
                array(
                    'yahoo_japan_config_cache' => array(
                        'locale' => array(
                            'enabled' => true,
                        ),
                    ),
                ),
                true,
                true,
                null,
                null,
                true,
            ),
            // has $config['locale'], enabled = true, has locale, has no listener_priority
            array(
                array(
                    'yahoo_japan_config_cache' => array(
                        'locale' => array(
                            'enabled' => true,
                            'locales' => $locales,
                        ),
                    ),
                ),
                true,
                true,
                $locales,
                $defaultPriority,
                false,
            ),
            // has $config['locale'], enabled = true, has locale, has listener_priority, priority < 16
            array(
                array(
                    'yahoo_japan_config_cache' => array(
                        'locale' => array(
                            'enabled' => true,
                            'locales' => $locales,
                            'listener_priority' => $priority = 15,
                        ),
                    ),
                ),
                true,
                true,
                $locales,
                $priority,
                false,
            ),
            // has $config['locale'], enabled = true, has locale, has listener_priority, priority >= 16 (exception)
            array(
                array(
                    'yahoo_japan_config_cache' => array(
                        'locale' => array(
                            'enabled' => true,
                            'locales' => $locales,
                            'listener_priority' => $priority = 16,
                        ),
                    ),
                ),
                true,
                true,
                null,
                null,
                true,
            ),
            array(
                array(
                    'yahoo_japan_config_cache' => array(
                        'locale' => array(
                            'enabled' => true,
                            'locales' => $locales,
                            'listener_priority' => $priority = 17,
                        ),
                    ),
                ),
                true,
                true,
                null,
                null,
                true,
            ),
        );
    }
}
