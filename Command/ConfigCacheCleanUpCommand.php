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
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ConfigCacheCleanUpCommand is a command to clear the cache in the temporary directory and the Symfony cache directory.
 */
class ConfigCacheCleanUpCommand extends ContainerAwareCommand
{
    protected $cleanup;
    protected $filesystem;
    protected $cacheDirectory;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config-cache:cleanup')
            ->setDescription('Cleans up the cache')
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->cleanup        = $this->getContainer()->get('yahoo_japan_config_cache.cache_cleanup');
        $this->filesystem     = $this->getContainer()->get('filesystem');
        $this->cacheDirectory = $this->getContainer()->getParameter('kernel.cache_dir');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // remove temporary directory
        $this->cleanup->cleanUp();
        // also remove Symfony cache directory created by running AppKernel::boot()
        $this->filesystem->remove($this->cacheDirectory);

        $output->writeln("Cleaned up the cache");
    }
}
