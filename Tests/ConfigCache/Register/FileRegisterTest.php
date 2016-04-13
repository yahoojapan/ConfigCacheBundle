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

use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\DirectoryResource;
use YahooJapan\ConfigCacheBundle\ConfigCache\Resource\FileResource;
use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

class FileRegisterTest extends TestCase
{
    /**
     * @dataProvider enabledProvider
     */
    public function testEnabled($resource, $expected)
    {
        $file = $this->createFileRegisterMock();
        $this->assertSame($expected, $file->enabled($resource));
    }

    /**
     * @return array($resource, $expected)
     */
    public function enabledProvider()
    {
        return array(
            // exists and is FileResource
            array(new FileResource(__DIR__.'/../../Fixtures/test_service1.yml'), true),
            // exists and is not FileResource
            array(new DirectoryResource(__DIR__.'/../../Fixtures'), false),
            // not exists
            array(new FileResource('not_exists.yml'), false),
        );
    }

    /**
     * @dataProvider hasNoAliasProvider
     */
    public function testHasNoAlias(array $files, $expected)
    {
        $fileRegister = $this->createFileRegisterMock();
        foreach ($files as $file) {
            $fileRegister->add($file);
        }
        $actual = $this->util->invoke($fileRegister, 'hasNoAlias');
        $this->assertSame($expected, $actual);
    }

    /**
     * @return array($files, $expected)
     */
    public function hasNoAliasProvider()
    {
        return array(
            // FileResource without alias
            array(
                array(new FileResource(__DIR__.'/../../Fixtures/test_service1.yml')),
                true,
            ),
            // FileResource with alias
            array(
                array(new FileResource(__DIR__.'/../../Fixtures/test_service1.yml', null, 'test_alias')),
                false,
            ),
            // mixed
            array(
                array(
                    new FileResource(__DIR__.'/../../Fixtures/test_service1.yml'),
                    new FileResource(__DIR__.'/../../Fixtures/test_service1.yml', null, 'test_alias'),
                ),
                true,
            ),
        );
    }

    protected function createFileRegisterMock(array $methods = null)
    {
        return $this->util->createMock('YahooJapan\ConfigCacheBundle\ConfigCache\Register\FileRegister', $methods);
    }
}
