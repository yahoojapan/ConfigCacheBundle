<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ConfigurationCommand is a command to generate a code sample of Configuration class.
 */
class ConfigurationCommand extends ContainerAwareCommand
{
    protected $bundle;
    protected $loader;
    protected $fileName;
    protected $bundlePath;
    protected $resourceDir = '/Resources/config';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:configuration')
            ->setDescription('Generates a Configuration sample')
            ->addOption('bundle', 'b', InputOption::VALUE_REQUIRED, 'A Bundle name of a config file')
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'A config file name')
            ->addOption(
                'configuration', 'c', InputOption::VALUE_OPTIONAL, 'A Configuration class name', 'Configuration'
            )
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->bundle   = $this->getContainer()->get('kernel')->getBundle($input->getOption('bundle'));
        $this->loader   = $this->getContainer()->get('yahoo_japan_config_cache.delegating_loader');
        $this->fileName = $this->bundle->getPath().$this->resourceDir.'/'.$input->getOption('file');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // load file
        $config = $this->loader->load($this->fileName);

        // root key check
        $rootKey = $this->bundle->getContainerExtension()->getAlias();
        if (!array_key_exists($rootKey, $config)) {
            $message = "The config file[{$this->fileName}] root key must be a bundle alias[{$rootKey}]";
            throw new \RuntimeException($message);
        }

        // generate Configuration
        $code = $this->createGenerator($config[$rootKey], $input->getOption('configuration'))->generate();

        // write file
        $path = $this->createPath($input->getOption('configuration'));
        file_put_contents($path, $code);
        $output->writeln("Generated file {$path}");
    }

    /**
     * Creates a Configuration file path.
     *
     * @return string
     */
    protected function createPath($className)
    {
        return $this->getBundlePath()."/DependencyInjection/{$className}.php";
    }

    /**
     * Gets a Bundle file path.
     *
     * @return string
     */
    protected function getBundlePath()
    {
        return $this->bundlePath ?: $this->bundle->getPath();
    }

    /**
     * Creates a ConfigurationGenerator.
     *
     * @param array  $config
     * @param string $className
     *
     * @return ConfigurationGenerator
     */
    protected function createGenerator(array $config, $className)
    {
        return new ConfigurationGenerator($config, $this->bundle, $className);
    }
}
