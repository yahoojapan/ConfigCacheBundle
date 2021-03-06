<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use YahooJapan\ConfigCacheBundle\DependencyInjection\Compiler\CacheWarmerPass;
use YahooJapan\ConfigCacheBundle\DependencyInjection\Compiler\LocalePass;
use YahooJapan\ConfigCacheBundle\DependencyInjection\Compiler\RegisterPass;
use YahooJapan\ConfigCacheBundle\DependencyInjection\Compiler\RestorableCachePass;
use YahooJapan\ConfigCacheBundle\YahooJapanConfigCacheBundle;

class YahooJapanConfigCacheBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = new ContainerBuilder();
        $bundle    = new YahooJapanConfigCacheBundle();
        $bundle->build($container);
        $expected = array(new RegisterPass(), new CacheWarmerPass(), new LocalePass(), new RestorableCachePass());
        $actual   = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $this->assertEquals($expected, $actual);
    }
}
