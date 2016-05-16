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
use Doctrine\Common\Cache\PhpFileCache;

/**
 * RestorablePhpFileCache restores by re-creating the caches to the temporary directory or cache directory.
 */
class RestorablePhpFileCache extends PhpFileCache
{
    const TAG_RESTORABLE_CACHE  = 'config_cache.restorable';
    const TEMP_DIRECTORY_PREFIX = 'yahoo_japan_config_cache';

    protected $restoringDirectory;
    protected $filesystem;

    /**
     * Saves the cache to the temporary directory.
     *
     * @param string $id
     */
    public function saveToTemp($id)
    {
        if ($this->contains($id)) {
            $data = $this->fetch($id);
            $this->prepareTemporaryDirectory();
            $this->save($id, $data);
            $this->restoreDirectory();
        }
    }

    /**
     * Restores the cache to the cache directory.
     *
     * @param string $id
     */
    public function restore($id)
    {
        $this->prepareTemporaryDirectory();
        if ($this->contains($id)) {
            $data = $this->fetch($id);
            $this->restoreDirectory();
            $this->save($id, $data);
        } else {
            $this->restoreDirectory();
        }
    }

    /**
     * Sets a Filesystem.
     *
     * @param Filesystem $filesystem
     *
     * @return RestorablePhpFileCache
     */
    public function setFilesystem(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;

        return $this;
    }

    /**
     * Sets a directory.
     *
     * @param string $directory
     *
     * @return RestorablePhpFileCache
     */
    protected function setDirectory($directory)
    {
        $this->directory = realpath($directory);
        $this->directoryStringLength = strlen($this->directory);

        return $this;
    }

    /**
     * Sets an original restoring directory.
     *
     * @param string $directory
     *
     * @return RestorablePhpFileCache
     */
    protected function setRestoringDirectory($directory)
    {
        $this->restoringDirectory = $directory;

        return $this;
    }

    /**
     * Prepares temporary directory to re-create cache.
     *
     * @return RestorablePhpFileCache
     */
    protected function prepareTemporaryDirectory()
    {
        $currentDirectory = $this->getDirectory();
        $this->setRestoringDirectory($currentDirectory);

        $temporaryDirectory = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .self::TEMP_DIRECTORY_PREFIX
            .$currentDirectory
            ;

        // mkdir before setDirectory()
        $this->filesystem->mkdir($temporaryDirectory);

        return $this->setDirectory($temporaryDirectory);
    }

    /**
     * Restores an original directory.
     *
     * @return RestorablePhpFileCache
     */
    protected function restoreDirectory()
    {
        return $this->setDirectory($this->restoringDirectory);
    }
}
