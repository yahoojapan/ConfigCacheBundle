<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache\Util;

/**
 * The array access utility interface.
 */
interface ArrayAccessInterface
{
    /**
     * Gets an array value by path.
     *
     * @param string $path
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($path, $default = array());

    /**
     * Replaces parameters.
     *
     * @param array $parameters
     *
     * @return ArrayAccessInterface
     */
    public function replace(array $parameters);
}
