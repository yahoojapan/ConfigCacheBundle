<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache\Loader;

use Symfony\Component\Config\Util\XmlUtils;

/**
 * XmlFileLoader loads XML files user configurations.
 */
class XmlFileLoader extends Loader
{
    /**
     * Returns true if this class supports the given resource.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return bool true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'xml' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    /**
     * Loads internal.
     *
     * @param string $file
     *
     * @return \SimpleXMLElement
     */
    protected function loadFile($file)
    {
        try {
            $dom = XmlUtils::loadFile($file);
        } catch (\Exception $e) {
            throw new \Exception(sprintf('Unable to parse file "%s".', $file), $e->getCode());
        }

        return simplexml_import_dom($dom);
    }
}
