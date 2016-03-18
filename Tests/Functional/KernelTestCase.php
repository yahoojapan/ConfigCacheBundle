<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase as BaseKernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

class KernelTestCase extends BaseKernelTestCase
{
    protected $testCase;
    protected $util;

    protected function setUp()
    {
        parent::setUp();

        $this->deleteTmpDir();
        static::bootKernel();
        $this->testCase = static::$kernel->getTestCase();
        if (is_null($this->util)) {
            $this->util = $this->createUtil();
        }
    }

    protected function deleteTmpDir($testCase = null)
    {
        if (!file_exists($dir = sys_get_temp_dir().'/'.Kernel::VERSION.'/'.$testCase ?: $this->testCase())) {
            return;
        }

        $fs = new Filesystem();
        $fs->remove($dir);
    }

    protected static function getKernelClass()
    {
        require_once __DIR__.'/app/AppKernel.php';

        return 'YahooJapan\ConfigCacheBundle\Tests\Functional\app\AppKernel';
    }

    protected static function createKernel(array $options = array())
    {
        $class = self::getKernelClass();

        return new $class('default', isset($options['debug']) ? $options['debug'] : true);
    }

    protected function createUtil()
    {
        return new TestCaseUtil($this);
    }
}
