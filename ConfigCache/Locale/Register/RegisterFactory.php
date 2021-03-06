<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache\Locale\Register;

use YahooJapan\ConfigCacheBundle\ConfigCache\Register\RegisterFactory as BaseRegisterFactory;

/**
 * RegisterFactory creates register objects.
 */
class RegisterFactory extends BaseRegisterFactory
{
    protected $serviceRegisterClass = 'YahooJapan\ConfigCacheBundle\ConfigCache\Locale\Register\ServiceRegister';
}
