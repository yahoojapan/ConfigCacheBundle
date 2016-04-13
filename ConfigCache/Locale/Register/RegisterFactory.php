<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015-2016 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\ConfigCache\Locale\Register;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use YahooJapan\ConfigCacheBundle\ConfigCache\Register\RegisterFactory as BaseRegisterFactory;

/**
 * RegisterFactory creates register objects.
 */
class RegisterFactory extends BaseRegisterFactory
{
    /**
     * {@inheritdoc}
     */
    public function createServiceRegister(ContainerBuilder $container)
    {
        $idBuilder     = $this->idBuilder ?: $this->createIdBuilder();
        $configuration = $this->configuration ?: $this->createConfigurationRegister();

        return $this->serviceRegister = new ServiceRegister($container, $idBuilder, $configuration);
    }
}
