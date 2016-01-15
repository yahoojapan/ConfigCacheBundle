<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache\Loader;

use Symfony\Component\Yaml\Yaml;

/**
 * YamlFileLoader loads YAML files user configurations.
 */
class YamlFileLoader extends Loader
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
        return is_string($resource) && 'yml' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    /**
     * Loads internal.
     *
     * @param string $file
     *
     * @return array
     */
    protected function loadFile($file)
    {
        if (!stream_is_local($file)) {
            throw new \InvalidArgumentException(sprintf('This is not a local file "%s".', $file));
        }

        if (!file_exists($file)) {
            throw new \InvalidArgumentException(sprintf('The service file "%s" is not valid.', $file));
        }

        try {
            $array = Yaml::parse(file_get_contents($file));
        } catch (\Exception $e) {
            throw new \Exception(sprintf("Yaml parse failed[%s]", $file));
        }

        foreach ($this->loaders as $loader) {
            $array = $loader->load($array);
        }

        return $array;
    }
}
