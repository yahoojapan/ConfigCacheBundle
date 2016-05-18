<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\CacheClearer;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache;

/**
 * The CacheSaver saves cache files to the temporary directory.
 */
class CacheSaver implements CacheClearerInterface
{
    protected $configs = array();

    /**
     * {@inheritdoc}
     */
    public function clear($cacheDir)
    {
        foreach ($this->configs as $config) {
            $config->save();
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
     * @return CacheSaver
     */
    public function addConfig(ConfigCache $config)
    {
        $this->configs[] = $config;

        return $this;
    }
}
