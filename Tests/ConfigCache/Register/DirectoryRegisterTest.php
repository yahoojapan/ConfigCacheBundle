<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache\Register;

use Symfony\Component\Filesystem\Filesystem;
use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\DirectoryResource;
use YahooJapan\ConfigCacheBundle\Tests\ConfigCache\RegisterTestCase;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\RegisterConfiguration;

class DirectoryRegisterTest extends RegisterTestCase
{
    // post-processing here to operate file on testRegisterInternal, testFindFilesByDirectory
    protected function tearDown()
    {
        if (file_exists($this->getTmpDir())) {
            $file = new Filesystem();
            $file->remove($this->getTmpDir());
        }
    }

    protected function getTmpDir()
    {
        return sys_get_temp_dir().'/yahoo_japan_config_cache';
    }

    /**
     * @dataProvider findFilesProvider
     */
    public function testFindFiles($resource, $excludes, $files, $expected)
    {
        // create directory/file
        $file = new Filesystem();
        if (!$file->exists($this->getTmpDir())) {
            $file->mkdir($this->getTmpDir());
        }
        if ($files > 0) {
            foreach (range(1, $files) as $i) {
                $file->touch($this->getTmpDir()."/testFindFilesByDirectory{$i}");
            }
        }

        $register = $this->createRegisterMock();
        $serviceRegister = $this->util->getProperty($register, 'directory');
        if (is_string($expected) && class_exists($expected)) {
            $this->setExpectedException($expected);
        }
        $finder = $this->util->invoke($serviceRegister, 'findFiles', $resource, $excludes);

        $results = array();
        foreach ($finder as $file) {
            $results[] = (string) $file;
        }
        sort($results); // sort by file name
        $this->assertSame($expected, $results);
    }

    /**
     * @return array ($resource, $excludes, $files, $expected)
     */
    public function findFilesProvider()
    {
        $configuration = new RegisterConfiguration();

        return array(
            // no file
            array(
                new DirectoryResource($this->getTmpDir(), $configuration),
                array(),
                0,
                array(),
            ),
            // no directory (generally not occurring for checking is_dir())
            array(
                new DirectoryResource(__DIR__.'/no_exists', $configuration),
                array(),
                0,
                '\InvalidArgumentException',
            ),
            // a file
            array(
                new DirectoryResource($this->getTmpDir(), $configuration),
                array(),
                1,
                array($this->getTmpDir()."/testFindFilesByDirectory1"),
            ),
            // greater than two file
            array(
                new DirectoryResource($this->getTmpDir(), $configuration),
                array(),
                2,
                array(
                    $this->getTmpDir()."/testFindFilesByDirectory1",
                    $this->getTmpDir()."/testFindFilesByDirectory2",
                ),
            ),
            // file exists, enable excluding
            array(
                new DirectoryResource($this->getTmpDir(), $configuration),
                array(
                    $this->getTmpDir()."/testFindFilesByDirectory3",
                    $this->getTmpDir()."/testFindFilesByDirectory4",
                ),
                5,
                array(
                    $this->getTmpDir()."/testFindFilesByDirectory1",
                    $this->getTmpDir()."/testFindFilesByDirectory2",
                    $this->getTmpDir()."/testFindFilesByDirectory5",
                ),
            ),
        );
    }
}
