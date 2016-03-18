<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\Functional\app;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    protected $testCase = 'yahoo_japan_config_cache';

    public function registerBundles()
    {
        $bundles = array(
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \YahooJapan\ConfigCacheBundle\Tests\Functional\Bundle\RegisterBundle\RegisterBundle(),
            new \YahooJapan\ConfigCacheBundle\Tests\Functional\Bundle\RegisterAllBundle\RegisterAllBundle(),
            new \YahooJapan\ConfigCacheBundle\YahooJapanConfigCacheBundle(),
        );

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return sys_get_temp_dir().'/'.Kernel::VERSION.'/'.$this->testCase.'/cache/'.$this->environment;
    }

    public function getLogDir()
    {
        return sys_get_temp_dir().'/'.Kernel::VERSION.'/'.$this->testCase.'/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/'.$this->environment.'.yml');
    }

    public function getTestCase()
    {
        return $this->testCase;
    }
}
