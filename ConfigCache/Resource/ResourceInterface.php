<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache\Resource;

/**
 * ResourceInterface is an interface to combine resources and a Configuration.
 */
interface ResourceInterface
{
    /**
     * Returns the configuration.
     *
     * @return \Symfony\Component\Config\Definition\ConfigurationInterface
     */
    public function getConfiguration();

    /**
     * Sets the resource.
     *
     * @param string $resource
     *
     * @return ResourceInterface
     */
    public function setResource($resource);

    /**
     * Return true if the resource exists.
     *
     * @return bool
     */
    public function exists();
}
