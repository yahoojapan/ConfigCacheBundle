<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache\Locale;

use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\Register\RegisterFactory;
use YahooJapan\ConfigCacheBundle\ConfigCache\Register;

/**
 * RegisterLocale registers a translated cache service by some bundles.
 */
class RegisterLocale extends Register
{
    /**
     * Registers a service by a bundle with locale.
     *
     * Registers Locale\ConfigCache services, and tags to set locale with ConfigCache LocaleListener.
     */
    public function register()
    {
        $this
            ->setTag(ConfigCache::TAG_LOCALE)
            ->initializeResources()
            ->registerInternal()
            ;
    }

    /**
     * Registers a service by all bundles with locale.
     */
    public function registerAll()
    {
        $this
            ->setTag(ConfigCache::TAG_LOCALE)
            ->initializeAllResources($this->container->getParameter('kernel.bundles'))
            ->registerInternal()
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function createRegisterFactory()
    {
        return new RegisterFactory();
    }
}
