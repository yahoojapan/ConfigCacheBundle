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

/**
 * ConfigCacheInterface is the interface which manages user configuration files with locale.
 */
interface ConfigCacheInterface
{
    /**
     * Sets referable locales to create cache files by locales.
     *
     * @param array $locales
     *
     * @return ConfigCache
     */
    public function setReferableLocales(array $locales);

    /**
     * Sets a default locale.
     *
     * Supposed when registering services, %kernel.default_locale% will be set.
     *
     * @param string $locale
     *
     * @return ConfigCache
     */
    public function setDefaultLocale($locale);

    /**
     * Sets a current locale to find cache file array.
     *
     * @param string $locale
     *
     * @return ConfigCache
     */
    public function setCurrentLocale($currentLocale);
}
