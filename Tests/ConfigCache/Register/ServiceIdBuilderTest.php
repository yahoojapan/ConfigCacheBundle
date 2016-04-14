<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache\Register;

use YahooJapan\ConfigCacheBundle\ConfigCache\Register\ServiceIdBuilder;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\RegisterConfiguration;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class ServiceIdBuilderTest extends TestCase
{
    /**
     * @dataProvider parseServiceIdProvider
     */
    public function testParseServiceId($name, $expected)
    {
        $this->assertSame($expected, ServiceIdBuilder::parseServiceId($name));
    }

    /**
     * @return array($name, $expected)
     */
    public function parseServiceIdProvider()
    {
        return array(
            // "Bundle" suffix
            array('YahooJapanConfigCacheBundle', 'yahoo_japan_config_cache'),
            // "Bundle" include (generally not occurring)
            array('YahooJapanConfigCacheBundleTest', 'yahoo_japan_config_cache_bundle_test'),
            // "Bundle" not include (generaty not occurring)
            array('YahooJapanConfigCacheTest', 'yahoo_japan_config_cache_test'),
        );
    }

    /**
     * @dataProvider buildIdProvider
     */
    public function testBuildId($suffix, $expected)
    {
        $builder = new ServiceIdBuilder();
        $this->assertSame($expected, $builder->buildId($suffix));
    }

    /**
     * @return array ($suffix, $expected)
     */
    public function buildIdProvider()
    {
        return array(
            array(array('hoge'), 'config.hoge'),
            array(array('hoge', 'fuga'), 'config.hoge.fuga'),
        );
    }

    /**
     * @dataProvider buildConfigurationIdProvider
     */
    public function testBuildConfigurationId($configuration, $expected)
    {
        $builder = new ServiceIdBuilder();
        $this->assertSame($expected, $builder->buildConfigurationId($configuration));
    }

    /**
     * @return array ($bundleId, $configuration, $expected)
     */
    public function buildConfigurationIdProvider()
    {
        return array(
            // Configuration but no "_" has
            array(
                new \YahooJapan\ConfigCacheBundle\Tests\Fixtures\Configuration(),
                'config.configuration.yahoo_japan.config_cache_bundle.tests.fixtures.configuration',
            ),
            // Configuration and "_" has
            array(
                new RegisterConfiguration(),
                'config.configuration.yahoo_japan.config_cache_bundle.tests.fixtures.register_configuration',
            ),
        );
    }

    public function testSetPrefix()
    {
        $builder = new ServiceIdBuilder();
        $this->assertSame('config', $builder->getPrefix());
        $builder->setPrefix($expected = 'zzz');
        $this->assertSame($expected, $builder->getPrefix());
    }
}
