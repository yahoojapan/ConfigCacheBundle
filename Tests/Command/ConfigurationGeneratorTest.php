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

/**
 * Only exception test.
 */
class ConfigurationGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \LogicException
     */
    public function testGenerateException()
    {
        $generator = $this->getMockBuilder('YahooJapan\ConfigCacheBundle\Command\ConfigurationGenerator')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock()
            ;
        $generator->generate();
    }
}
