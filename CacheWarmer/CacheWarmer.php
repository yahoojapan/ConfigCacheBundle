<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache;

/**
 * The CacheWarmer warm-ups the caches that are tagged ConfigCache services.
 */
class CacheWarmer implements CacheWarmerInterface
{
    protected $configs = array();

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function warmUp($cacheDir)
    {
        foreach ($this->configs as $config) {
            $config->create();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * Adds a ConfigCache.
     *
     * @param ConfigCache $config
     *
     * @return CacheWarmerListener
     */
    public function addConfig(ConfigCache $config)
    {
        $this->configs[] = $config;

        return $this;
    }
}
