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

use Symfony\Component\Translation\TranslatorInterface;
use YahooJapan\ConfigCacheBundle\ConfigCache\Loader\ArrayLoader as BaseArrayLoader;

/**
 * ArrayLoader loads an array converting user configurations.
 */
class ArrayLoader extends BaseArrayLoader implements TranslationLoaderInterface
{
    protected $locale;
    protected $translator;

    /**
     * Constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Sets a locale.
     *
     * @param string $locale
     *
     * @return YamlFileLoader
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function walkInternal(&$value, $nouse)
    {
        if ($this->translator->getCatalogue()->has($value)) {
            $value = $this->translator->trans($value);
        }
    }

    /**
     * Walks by locale internal processing.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param string &$value
     * @param string $nouse
     *
     * @return void
     */
    protected function walkByLocaleInternal(&$value, $nouse)
    {
        if ($this->translator->getCatalogue($this->locale)->has($value)) {
            $value = $this->translator->trans($value, array(), null, $this->locale);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getInternalMethod()
    {
        if (is_null($this->locale)) {
            return parent::getInternalMethod();
        } else {
            return 'walkByLocaleInternal';
        }
    }
}
