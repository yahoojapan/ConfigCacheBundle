<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache\Util;

/**
 * The array access utility by dotted path.
 */
class ArrayAccess implements ArrayAccessInterface
{
    protected $parameters;

    /**
     * Constructor.
     *
     * @param array $parameters
     */
    public function __construct(array $parameters = array())
    {
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function get($path, $default = array())
    {
        if (isset($this->parameters[$path])) {
            return $this->parameters[$path];
        }

        $parameters = $this->parameters;
        foreach (explode('.', $path) as $key) {
            if ((!isset($parameters[0]) && isset($parameters[$key]))
                || (is_array($parameters) && array_key_exists($key, $parameters))
            ) {
                $parameters = $parameters[$key];
            } else {
                return $default;
            }
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function replace(array $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Creates a self instance.
     *
     * @param array $parameters
     *
     * @return ArrayAccess
     */
    public static function create(array $parameters)
    {
        return new static($parameters);
    }
}
