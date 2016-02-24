<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\Command;

use YahooJapan\ConfigCacheBundle\Tests\Functional\TestCase;

/**
 * Only exception test.
 */
class ConfigurationGeneratorTest extends TestCase
{
    /**
     * @expectedException \LogicException
     */
    public function testGenerateException()
    {
        $this->util->createMock('YahooJapan\ConfigCacheBundle\Command\ConfigurationGenerator')->generate();
    }
}
