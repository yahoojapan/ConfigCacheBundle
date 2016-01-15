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

use YahooJapan\ConfigCacheBundle\ConfigCache\Loader\YamlFileLoader as BaseYamlFileLoader;

/**
 * YamlFileLoader loads YAML files translated user configurations.
 */
class YamlFileLoader extends BaseYamlFileLoader implements TranslationLoaderInterface
{
    /**
     * Sets a locale.
     *
     * @param string $locale
     *
     * @return YamlFileLoader
     */
    public function setLocale($locale)
    {
        foreach ($this->loaders as $loader) {
            if ($loader instanceof TranslationLoaderInterface) {
                $loader->setLocale($locale);
            }
        }

        return $this;
    }
}
