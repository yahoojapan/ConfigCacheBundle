<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use YahooJapan\ConfigCacheBundle\DependencyInjection\Compiler\CacheWarmerPass;
use YahooJapan\ConfigCacheBundle\DependencyInjection\Compiler\LocalePass;
use YahooJapan\ConfigCacheBundle\DependencyInjection\Compiler\RegisterPass;
use YahooJapan\ConfigCacheBundle\DependencyInjection\Compiler\RestorableCachePass;

class YahooJapanConfigCacheBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RegisterPass());
        $container->addCompilerPass(new CacheWarmerPass());
        $container->addCompilerPass(new LocalePass());
        $container->addCompilerPass(new RestorableCachePass());
    }
}
