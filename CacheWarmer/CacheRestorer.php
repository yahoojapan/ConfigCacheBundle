<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache;

/**
 * The CacheRestorer restores cache files to the cache directory.
 */
class CacheRestorer implements CacheWarmerInterface
{
    protected $configs = array();

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        foreach ($this->configs as $config) {
            $config->restore();
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
     * Adds a ConfigCache with RestorablePhpFileCache.
     *
     * @param ConfigCache $config
     *
     * @return CacheRestorer
     */
    public function addConfig(ConfigCache $config)
    {
        $this->configs[] = $config;

        return $this;
    }
}
