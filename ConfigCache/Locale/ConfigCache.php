<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache\Locale;

use YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache as BaseConfigCache;
use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\Loader\TranslationLoaderInterface;

/**
 * ConfigCache manages user configuration files with locale.
 */
class ConfigCache extends BaseConfigCache implements ConfigCacheInterface
{
    const TAG_LOCALE = 'config.locale';

    // for createing cache
    protected $referableLocales = array();
    // for getting cache
    protected $defaultLocale;
    protected $currentLocale;

    /**
     * {@inheritdoc}
     */
    public function setReferableLocales(array $locales)
    {
        $this->referableLocales = $locales;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentLocale($currentLocale)
    {
        $this->currentLocale = $currentLocale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        if (!($this->loader instanceof TranslationLoaderInterface)) {
            throw new \Exception('Loader must be a translation loader interface');
        }

        foreach ($this->referableLocales as $locale) {
            if (!$this->cache->contains($this->getKey($locale))) {
                $this->loader->setLocale($locale);
                $this->createInternal($locale);
            }
        }
    }

    /**
     * Creates PHP cache file internal processing.
     *
     * this method has a $locale argument for executing findAll() but no cache.
     *
     * @param string $locale
     *
     * @return array
     */
    protected function createInternal($locale = null)
    {
        $data = $this->load();
        $this->cache->save($this->getKey($locale), $data);

        return $data;
    }

    /**
     * Gets key with locale.
     *
     * $locale argument has the potential to become null when findAll().
     *
     * @param string $locale
     *
     * @return string
     */
    protected function getKey($locale = null)
    {
        if (is_null($locale)) {
            $locale = $this->getLocale();

            // no chance of this block
            // because of having getLocale() return value (always configured on setDefaultLocale()).
            if (is_null($locale)) {
                throw new \Exception('Locale must be set.');
            }
        }

        return $this->key.".{$locale}";
    }

    /**
     * Gets a locale.
     *
     * @return string
     */
    protected function getLocale()
    {
        return $this->currentLocale ?: $this->defaultLocale;
    }
}