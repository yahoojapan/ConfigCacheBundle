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

use Symfony\Component\Filesystem\Filesystem;
use YahooJapan\ConfigCacheBundle\ConfigCache\RestorablePhpFileCache;
use YahooJapan\ConfigCacheBundle\ConfigCache\SaveAreaBuilder;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class RestorablePhpFileCacheTest extends TestCase
{
    protected $builder;

    protected function setUp()
    {
        parent::setUp();

        $this->builder = $this->createSaveAreaBuilder();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $filesystem = new Filesystem();
        $filesystem->remove($this->getRootCacheDirectory());
        $filesystem->remove($this->builder->buildPrefix());
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testSaveToTemp($contains)
    {
        $id   = 'zzz';
        $data = array(
            'key1' => 'value1',
            'key2' => 'value2',
        );
        $cache = $this->createPhpFileCache();

        // first create cache in cache directory if contains
        if ($contains) {
            $cache->save($id, $data);
        }
        // save to temporary directory
        $cache->saveToTemp($id);
        // assert
        $this->assertSame($contains, $cache->contains($id));
    }

    /**
     * Use testSave, testRestore, testSetDirectory
     *
     * @return array($booleans)
     */
    public function booleanProvider()
    {
        return array(
            array(true),
            array(false),
        );
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testRestore($contains)
    {
        $id   = 'zzz';
        $data = array(
            'key1' => 'value1',
            'key2' => 'value2',
        );
        $cache = $this->createPhpFileCache();

        // create cache and save to temporary directory
        if ($contains) {
            $cache->save($id, $data);
            $cache->saveToTemp($id);
        }
        // restore cache
        $cache->restore($id);
        // assert
        $this->assertSame($contains, $cache->contains($id));
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testSetDirectory($hasDirectory)
    {
        $cache      = $this->createPhpFileCache();
        $filesystem = new Filesystem();
        if ($hasDirectory) {
            $directory = $this->getCacheDirectory();
            $filesystem->mkdir($directory);
        } else {
            $directory = '/dummy_directory';
        }

        $this->util->invoke($cache, 'setDirectory', $directory);
        // become false by realpath() if not exist
        $this->assertSame($hasDirectory ? $directory : false, $this->util->getProperty($cache, 'directory'));
    }

    public function testSetRestoringDirectory()
    {
        $cache = $this->createPhpFileCache();
        $directory = $this->getCacheDirectory();
        $this->util->invoke($cache, 'setRestoringDirectory', $directory);
        $this->assertSame($directory, $this->util->getProperty($cache, 'restoringDirectory'));
    }

    public function testPrepareTemporaryDirectory()
    {
        $cache = $this->createPhpFileCache();
        $directory = $this->getCacheDirectory();
        $this->util->invoke($cache, 'setDirectory', $directory);
        $this->util->invoke($cache, 'prepareTemporaryDirectory');

        $expectedTempDirectory = $this->builder->buildPrefix().$directory;
        $this->assertSame($expectedTempDirectory, $cache->getDirectory());
        $filesystem = new Filesystem();
        $this->assertTrue($filesystem->exists($expectedTempDirectory));
    }

    public function testRestoreDirectory()
    {
        $cache = $this->createPhpFileCache();
        $directory = $this->getCacheDirectory();
        $this->util->invoke($cache, 'setRestoringDirectory', $directory);
        $this->util->invoke($cache, 'restoreDirectory');
        $this->assertSame($directory, $this->util->getProperty($cache, 'directory'));
    }

    protected function getRootCacheDirectory()
    {
        return sys_get_temp_dir().'/yahoo_japan_config_cache';
    }

    protected function getCacheDirectory()
    {
        return $this->getRootCacheDirectory().'/restorable_php_file_cache_test';
    }

    protected function createPhpFileCache()
    {
        $cache = new RestorablePhpFileCache($this->getCacheDirectory(), '.php');
        $cache->setBuilder($this->builder);

        return $cache;
    }

    protected function createSaveAreaBuilder()
    {
        return new SaveAreaBuilder('test', new Filesystem());
    }
}
