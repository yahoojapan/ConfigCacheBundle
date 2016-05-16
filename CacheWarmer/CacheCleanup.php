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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use YahooJapan\ConfigCacheBundle\ConfigCache\RestorablePhpFileCache;

/**
 * The CacheCleanup is to clear the temporary directory.
 */
class CacheCleanup implements CacheWarmerInterface
{
    protected $filesystem;

    /**
     * Constructor.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->cleanUp();
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * Cleans up to clear the temporary directory after warming up.
     */
    public function cleanUp()
    {
        $this->filesystem->remove(sys_get_temp_dir().DIRECTORY_SEPARATOR.RestorablePhpFileCache::TEMP_DIRECTORY_PREFIX);
    }
}
