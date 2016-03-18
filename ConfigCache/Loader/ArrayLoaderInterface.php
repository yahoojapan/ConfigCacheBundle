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
 * ArrayLoaderInterface is the interface implemented by array converting loader classes.
 */
interface ArrayLoaderInterface
{
    /**
     * Loads an array of resource.
     *
     * @param array $resource an array loaded from the file.
     *
     * @return array
     */
    public function load(array $resource);
}
