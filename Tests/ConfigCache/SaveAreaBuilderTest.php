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

class SaveAreaBuilderTest extends TestCase
{
    protected $builder;
    protected $filesystem;
    protected $env = 'test';

    protected function setUp()
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->builder    = $this->createSaveAreaBuilder();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->filesystem->remove($this->builder->buildPrefix());
    }

    public function testBuild()
    {
        $directory = '/save_area_builder_test';
        $temporaryDirectory = $this->builder->build($directory);
        $this->assertTrue($this->filesystem->exists($temporaryDirectory));
        $this->assertSame($this->builder->buildPrefix().$directory, $temporaryDirectory);
    }

    public function testBuildPrefix()
    {
        $expected = sys_get_temp_dir().DIRECTORY_SEPARATOR.SaveAreaBuilder::TEMP_DIRECTORY_PREFIX.$this->env;
        $this->assertSame($expected, $this->builder->buildPrefix());
    }

    protected function createSaveAreaBuilder()
    {
        return new SaveAreaBuilder($this->env, $this->filesystem);
    }
}
