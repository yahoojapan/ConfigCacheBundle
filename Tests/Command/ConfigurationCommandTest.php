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

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use YahooJapan\ConfigCacheBundle\Command\ConfigurationCommand;
use YahooJapan\ConfigCacheBundle\ConfigCache\Definition\Processor;
use YahooJapan\ConfigCacheBundle\Tests\Functional\KernelTestCase;

class ConfigurationCommandTest extends KernelTestCase
{
    protected static $filesystem;
    protected $dir;
    protected $diDir;

    public static function setUpBeforeClass()
    {
        self::$filesystem = new Filesystem();
    }

    protected function setUp()
    {
        parent::setUp();

        $this->dir   = sys_get_temp_dir().'/'.$this->testCase;
        $this->diDir = $this->dir.'/DependencyInjection';
        self::$filesystem->mkdir($this->diDir, 0777, true);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->deleteTmpDir();
    }

    /**
     * @dataProvider executeProvider
     */
    public function testExecute($bundleName, $fileName, $className, $fqcn, $expectedException)
    {
        $application = new Application(static::$kernel);
        $application->add(new ConfigurationCommand());

        $command = $application->find('generate:configuration');

        // change bundlePath, fileName
        $this
            ->setProperty($command, 'bundlePath', $this->dir)
            ->setProperty($command, 'resourceDir', '/Tests/Fixtures')
            ;

        $options = array(
            '--bundle' => $bundleName,
            '--file'   => $fileName,
        );
        if (!is_null($className)) {
            $options['--configuration'] = $className;
            $fileCheckOnly = false;
        } else {
            $className = 'Configuration';
            $fileCheckOnly = true;
        }
        $commandTester = new CommandTester($command);
        if ($expectedException) {
            $this->setExpectedException('\Exception');
        }
        $commandTester->execute($options);

        // display and generate file
        $actual = $commandTester->getDisplay();

        // assert
        $expectedFileName = "{$this->diDir}/{$className}.php";
        $this->assertSame('Generated file '.$expectedFileName.PHP_EOL, $actual);
        $this->assertTrue(self::$filesystem->exists($expectedFileName));

        // return if the className is default("Configuration") due to require
        if ($fileCheckOnly) {
            return;
        }

        // assert file contents
        require $expectedFileName;
        $configuration = new $fqcn();
        $tree = $configuration->getConfigTreeBuilder()->buildTree();
        $processor = new Processor(); // use ConfigCacheBundle Processor
        $config    = Yaml::parse(file_get_contents($this->getProperty($command, 'fileName')));
        list($loaded, $node) = $processor->process(array(), $config, $tree);
        $alias = static::$kernel->getBundle($bundleName)->getContainerExtension()->getAlias();
        $this->assertSame($config[$alias], $loaded);
    }

    /**
     * @return array($bundleName, $fileName, $className, $fqcn, $expectedException)
     */
    public function executeProvider()
    {
        $bundleName = 'YahooJapanConfigCacheBundle';
        $rootName   = 'YahooJapan\ConfigCacheBundle\DependencyInjection';

        return array(
            // normal
            array($bundleName, 'test_command_normal.yml', $name = 'SampleConfiguration1', $rootName.'\\'.$name, false),
            // normal(for postIteration coverage)
            array($bundleName, 'test_command_normal2.yml', $name = 'SampleConfiguration2', $rootName.'\\'.$name, false),
            // no bundleName
            array(null, 'test_command_normal.yml', $name, null, true),
            // not registered bundleName
            array('NotRegisteredBundle', 'test_command_normal.yml', $name, null, true),
            // no fileName
            array($bundleName, null, $name = 'SampleConfiguration', null, true),
            // no configuration
            array($bundleName, 'test_command_normal.yml', null, $rootName.'\\Configuration', false),
            // invalid root key
            array($bundleName, 'test_command_error.yml', $name, null, true),
        );
    }

    protected function getProperty($instance, $name)
    {
        $property = new \ReflectionProperty($instance, $name);
        $property->setAccessible(true);

        return $property->getValue($instance);
    }

    protected function setProperty($instance, $name, $value)
    {
        $property = new \ReflectionProperty($instance, $name);
        $property->setAccessible(true);
        $property->setValue($instance, $value);

        return $this;
    }
}
