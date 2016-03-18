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

/**
 * ArrayLoader loads an array converting user configurations.
 */
abstract class ArrayLoader implements ArrayLoaderInterface
{
    // to convert method name
    protected $internalMethod = 'walkInternal';

    /**
     * Loads an array of resource.
     *
     * @param array $resource an array loaded from the file.
     *
     * @return array
     */
    public function load(array $resource)
    {
        $this->walkAllLeaves($resource);

        return $resource;
    }

    /**
     * Walks all leaves converting.
     *
     * @param array &$config
     *
     * @return void
     */
    protected function walkAllLeaves(&$config)
    {
        array_walk_recursive($config, array($this, $this->getInternalMethod()));
    }

    /**
     * Walks all leaves converting internal processing.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @note  implement child class
     * @param string &$value
     * @param string $nouse
     *
     * @return void
     */
    protected function walkInternal(&$value, $nouse)
    {
    }

    /**
     * Gets a transform internal method.
     *
     * @return string
     */
    protected function getInternalMethod()
    {
        return $this->internalMethod;
    }
}
