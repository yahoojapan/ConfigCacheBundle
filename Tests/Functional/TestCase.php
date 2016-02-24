<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\Functional;

/**
 * TestCase which is aware of TestCaseUtil.
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    protected $util;

    protected function setUp()
    {
        $this->initialize();
    }

    protected function initialize()
    {
        if (is_null($this->util)) {
            $this->util = $this->createUtil();
        }
    }

    protected function createUtil()
    {
        return new TestCaseUtil($this);
    }

    /**
     * will be called on dataProvider
     */
    protected function findUtil()
    {
        $this->initialize();

        return $this->util;
    }
}
