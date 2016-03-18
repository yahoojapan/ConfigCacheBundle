<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\Fixtures;

use YahooJapan\ConfigCacheBundle\ConfigCache\Loader\ArrayLoader as BaseArrayLoader;

class ArrayLoader extends BaseArrayLoader
{
    /**
     * {@inheritdoc}
     */
    protected function walkInternal(&$value, $nouse)
    {
        if ($value === '__REPLACE__') {
            $value = 'replaced_value';
        }
    }
}
