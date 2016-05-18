<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use YahooJapan\ConfigCacheBundle\Command\ConfigCacheCleanUpCommand;
use YahooJapan\ConfigCacheBundle\Tests\Functional\KernelTestCase;

class ConfigCacheCleanUpCommandTest extends KernelTestCase
{
    protected $filesystem;
    protected $tempDirectory;

    protected function setUp()
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->deleteTmpDir();
        if (!is_null($this->tempDirectory)) {
            $this->filesystem->remove($this->tempDirectory);
        }
    }

    public function testExecute()
    {
        $application = new Application(static::$kernel);
        $application->add(new ConfigCacheCleanUpCommand());
        $command = $application->find('config-cache:cleanup');

        // initialize command
        $input  = $this->util->createInterfaceMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->util->createInterfaceMock('Symfony\Component\Console\Output\OutputInterface');
        $this->util->invoke($command, 'initialize', $input, $output);

        // assert initialized directory
        $container           = static::$kernel->getContainer();
        $cacheDirectory      = $container->getParameter('kernel.cache_dir');
        $this->tempDirectory = $container->get('yahoo_japan_config_cache.save_area_builder')->buildPrefix();
        $this->filesystem->mkdir($this->tempDirectory);
        $this->assertTrue($this->filesystem->exists($cacheDirectory));
        $this->assertTrue($this->filesystem->exists($this->tempDirectory));

        // execute
        $commandTester = new CommandTester($command);
        $commandTester->execute(array());

        // assert (has no directory)
        $this->assertFalse($this->filesystem->exists($cacheDirectory));
        $this->assertFalse($this->filesystem->exists($this->tempDirectory));
    }
}
