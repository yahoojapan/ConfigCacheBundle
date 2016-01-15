<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache\Locale\Loader;

/**
 * TranslationLoaderInterface is the interface implemented by translation loader classes.
 */
interface TranslationLoaderInterface
{
    /**
     * Sets a locale.
     *
     * @param string $locale
     *
     * @return TranslationLoaderInterface
     */
    public function setLocale($locale);
}
