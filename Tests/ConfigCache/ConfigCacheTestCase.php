<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache;

use Doctrine\Common\Cache\PhpFileCache;
use Symfony\Component\Filesystem\Filesystem;
use YahooJapan\ConfigCacheBundle\ConfigCache\Loader\YamlFileLoader;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\ConfigCacheConfiguration;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

/**
 * This is an abstract class for preprocessing ConfigCacheTest, Locale\ConfigCacheTest.
 */
abstract class ConfigCacheTestCase extends TestCase
{
    protected $delete = true;
    protected static $tmpDir;
    protected static $cache;
    protected static $mtime;
    protected static $configCache = 'YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache';
    protected static $extension   = '.php';

    protected function tearDown()
    {
        $this->deleteTmpDir();
        $this->reload();
    }

    protected function deleteTmpDir()
    {
        if (!$this->delete) {
            return;
        }
        if (!file_exists($dir = self::$tmpDir)) {
            return;
        }

        $fs = new Filesystem();
        $fs->remove($dir);
    }

    protected function reload()
    {
        $this->util
            ->setProperty(self::$cache, 'resources', array())
            ->setProperty(self::$cache, 'config', array())
            ->setProperty(self::$cache, 'configuration', null)
            ->setProperty(self::$cache, 'arrayAccess', null)
            ->setProperty(self::$cache, 'key', null)
            ->setProperty(self::$cache, 'strict', true)
            ;

        // initialize flag fail to remove
        $this->delete = true;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public static function setUpBeforeClass()
    {
        self::$tmpDir = sys_get_temp_dir().'/yahoo_japan_config_cache';
        $phpFileCache = new PhpFileCache(self::$tmpDir, static::$extension);
        self::$cache  = new static::$configCache($phpFileCache, new YamlFileLoader());
        self::$cache->setConfiguration(new ConfigCacheConfiguration());
    }

    /**
     * for PhpFileCache::getFilename() pattern by version
     */
    public function getHashFileName($fileName)
    {
        $cache  = $this->util->getProperty(self::$cache, 'cache');
        $hashedFileName = $this->util->invoke($cache, 'getFilename', basename($fileName, static::$extension));

        return basename($hashedFileName);
    }
}
