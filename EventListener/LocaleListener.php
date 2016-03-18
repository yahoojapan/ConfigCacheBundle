<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCacheInterface;

/**
 * Sets the locale which the Request holds to each ConfigCache service.
 */
class LocaleListener
{
    protected $configs = array();

    /**
     * Runs on kernel request.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        $locale = $event->getRequest()->getLocale();
        foreach ($this->configs as $config) {
            $config->setCurrentLocale($locale);
        }
    }

    /**
     * Adds a ConfigCache.
     *
     * @param ConfigCacheInterface $config
     *
     * @return LocaleListener
     */
    public function addConfig(ConfigCacheInterface $config)
    {
        $this->configs[] = $config;

        return $this;
    }
}
