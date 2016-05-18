<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache;

use Symfony\Component\Filesystem\Filesystem;

/**
 * SaveAreaBuilder manages a temporary directory to save.
 */
class SaveAreaBuilder
{
    const TEMP_DIRECTORY_PREFIX = 'yahoo_japan_config_cache_';

    protected $env;
    protected $filesystem;

    /**
     * Constructor.
     *
     * @param string     $env
     * @param Filesystem $filesystem
     */
    public function __construct($env, Filesystem $filesystem)
    {
        $this->env        = $env;
        $this->filesystem = $filesystem;
    }

    /**
     * Builds a temporary directory and returns.
     *
     * @param string $directory
     *
     * @return string
     */
    public function build($directory)
    {
        $temporaryDirectory = $this->buildPrefix().$directory;
        $this->filesystem->mkdir($temporaryDirectory);

        return $temporaryDirectory;
    }

    /**
     * Builds a temporary directory prefix.
     *
     * @return string
     */
    public function buildPrefix()
    {
        return sys_get_temp_dir().DIRECTORY_SEPARATOR.self::TEMP_DIRECTORY_PREFIX.$this->env;
    }
}
