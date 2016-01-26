<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache;

use Doctrine\Common\Cache\PhpFileCache;
use Symfony\Component\Filesystem\Filesystem;
use YahooJapan\ConfigCacheBundle\ConfigCache\Loader\YamlFileLoader;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\ConfigCacheConfiguration;

/**
 * This is an abstract class for preprocessing ConfigCacheTest, Locale\ConfigCacheTest.
 */
abstract class ConfigCacheTestCase extends \PHPUnit_Framework_TestCase
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
        $property = new \ReflectionProperty(self::$cache, 'resources');
        $property->setAccessible(true);
        $property->setValue(self::$cache, array());
        $property = new \ReflectionProperty(self::$cache, 'config');
        $property->setAccessible(true);
        $property->setValue(self::$cache, array());
        $property = new \ReflectionProperty(self::$cache, 'configuration');
        $property->setAccessible(true);
        $property->setValue(self::$cache, null);
        $property = new \ReflectionProperty(self::$cache, 'arrayAccess');
        $property->setAccessible(true);
        $property->setValue(self::$cache, null);
        $property = new \ReflectionProperty(self::$cache, 'key');
        $property->setAccessible(true);
        $property->setValue(self::$cache, null);

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
        $property = new \ReflectionProperty(self::$cache, 'cache');
        $property->setAccessible(true);
        $cache  = $property->getValue(self::$cache);
        $method = new \ReflectionMethod($cache, 'getFilename');
        $method->setAccessible(true);
        $hashedFileName = $method->invoke($cache, basename($fileName, static::$extension));

        return basename($hashedFileName);
    }
}
