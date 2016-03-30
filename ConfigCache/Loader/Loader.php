<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache\Loader;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;

/**
 * FileLoader is the abstract class used by loading configuration file.
 */
abstract class Loader implements LoaderInterface
{
    protected $resolver;
    protected $loaders = array();

    /**
     * Loads a resource.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param string $resource File name
     * @param string $type     The resource type
     *
     * @return array
     */
    public function load($resource, $type = null)
    {
        return $this->loadFile($resource);
    }

    /**
     * Gets the loader resolver.
     *
     * @return LoaderResolverInterface A LoaderResolverInterface instance
     */
    public function getResolver()
    {
        return $this->resolver;
    }

    /**
     * Sets the loader resolver.
     *
     * @param LoaderResolverInterface $resolver A LoaderResolverInterface instance
     */
    public function setResolver(LoaderResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Adds an array loader.
     *
     * @param ArrayLoaderInterface $loader
     *
     * @return Loader
     */
    public function addLoader(ArrayLoaderInterface $loader)
    {
        $this->loaders[] = $loader;

        return $this;
    }

    /**
     * Adds array loaders.
     *
     * @param array $loaders
     *
     * @return Loader
     */
    public function addLoaders(array $loaders)
    {
        foreach ($loaders as $loader) {
            $this->addLoader($loader);
        }

        return $this;
    }

    /**
     * Loads internal processing.
     *
     * @param string $file
     *
     * @return array|\SimpleXMLElement
     */
    abstract protected function loadFile($file);
}
