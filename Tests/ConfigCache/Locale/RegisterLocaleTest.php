<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\ConfigCache\Locale;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Yaml\Yaml;
use YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCache;
use YahooJapan\ConfigCacheBundle\Tests\ConfigCache\RegisterTestCase;
use YahooJapan\ConfigCacheBundle\Tests\Fixtures\RegisterConfiguration;

class RegisterLocaleTest extends RegisterTestCase
{
    protected $registerClass = 'YahooJapan\ConfigCacheBundle\ConfigCache\Locale\RegisterLocale';

    public function testRegister()
    {
        $register = $this->getRegisterMock(array('setTag', 'registerInternal'));
        $register
            ->expects($this->once())
            ->method('setTag')
            ->with(ConfigCache::TAG_LOCALE)
            ->willReturnSelf()
            ;
        $register
            ->expects($this->once())
            ->method('registerInternal')
            ->willReturn(null)
            ;
        $register->register();
    }

    public function testRegisterAll()
    {
        $register = $this->getRegisterMock(array('setTag', 'registerInternal'));
        $register
            ->expects($this->once())
            ->method('setTag')
            ->with(ConfigCache::TAG_LOCALE)
            ->willReturnSelf()
            ;
        $register
            ->expects($this->once())
            ->method('registerInternal')
            ->with('all')
            ->willReturn(null)
            ;
        $register->registerAll();
    }

    public function testSetParameter()
    {
        list($register, $container) = $this->getRegisterMockAndContainerWithParameter();
        $expected = array(
            'Doctrine\Common\Cache\PhpFileCache'                             => 'php_file_cache.class',
            'YahooJapan\ConfigCacheBundle\ConfigCache\ConfigCache'           => 'config_cache.class',
            // added locale parameters
            'YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCache'    => 'locale.config_cache.class',
            'YahooJapan\ConfigCacheBundle\ConfigCache\Loader\YamlFileLoader' => 'yaml_file_loader.class',
            'YahooJapan\ConfigCacheBundle\ConfigCache\Loader\XmlFileLoader'  => 'xml_file_loader.class',
            'Symfony\Component\Config\Loader\LoaderResolver'                 => 'loader_resolver.class',
            'Symfony\Component\Config\Loader\DelegatingLoader'               => 'delegating_loader.class',
        );
        foreach ($expected as $className => $serviceId) {
            $this->assertTrue($container->getValue($register)->hasParameter("{$this->getCacheId()}.{$serviceId}"));
            $this->assertSame(
                $className,
                $container->getValue($register)->getParameter("{$this->getCacheId()}.{$serviceId}")
            );
        }
    }

    /**
     * test with mock for now
     */
    public function testSetCacheDefinition()
    {
        // register
        $register = $this->getRegisterMock(array('createCacheDefinition', 'buildId'));
        $bundleId = 'register_test';
        $configId = "config.{$bundleId}";
        $register
            ->expects($this->once())
            ->method('buildId')
            ->with($bundleId)
            ->willReturn($configId)
            ;
        // container
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('getParameter', 'setDefinition'))
            ->getMock()
            ;
        $defaultLocale = 'ja';
        $container
            ->expects($this->once())
            ->method('getParameter')
            ->with('kernel.default_locale')
            ->willReturn($defaultLocale)
            ;
        // definition
        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->setMethods(array('addMethodCall'))
            ->getMock()
            ;
        $definition
            ->expects($this->exactly(2))
            ->method('addMethodCall')
            ->withConsecutive(
                array('setDefaultLocale', array($defaultLocale)),
                array('setLoader', array(new Reference('yahoo_japan_config_cache.yaml_file_loader')))
            )
            ->willReturnSelf()
            ;
        // prepare register
        $register
            ->expects($this->once())
            ->method('createCacheDefinition')
            ->willReturn($definition)
            ;
        $property = new \ReflectionProperty($register, 'bundleId');
        $property->setAccessible(true);
        $property->setValue($register, $bundleId);
        // setDefinition
        $container
            ->expects($this->once())
            ->method('setDefinition')
            ->with($configId, $definition)
            ->willReturn(null)
            ;
        // set to RegisterLocale::container
        $property = new \ReflectionProperty($register, 'container');
        $property->setAccessible(true);
        $property->setValue($register, $container);
        // setCacheDefinition
        $method = new \ReflectionMethod($register, 'setCacheDefinition');
        $method->setAccessible(true);
        $method->invoke($register);
    }

    /**
     * assert getConfigCacheClass() override
     */
    public function testCreateCacheDefinition()
    {
        list($register, ) = $this->getRegisterMockAndContainerWithParameter();
        $id = 'register_test';

        // $register->bundleId = 'register_test'
        $bundleId = new \ReflectionProperty($register, 'bundleId');
        $bundleId->setAccessible(true);
        $bundleId->setValue($register, $id);

        // $register->configs = array('aaa' => 'bbb')
        $configs = new \ReflectionProperty($register, 'config');
        $configs->setAccessible(true);
        $configs->setValue($register, array('aaa' => 'bbb'));

        // $register->configuration = new Configuration()
        $configuration = new RegisterConfiguration();
        $property = new \ReflectionProperty($register, 'configuration');
        $property->setAccessible(true);
        $property->setValue($register, $configuration);

        // $register->createCacheDefinition()
        $method = new \ReflectionMethod($register, 'createCacheDefinition');
        $method->setAccessible(true);
        $definition = $method->invoke($register);

        // assert(ConfigCache)
        $this->assertSame('YahooJapan\ConfigCacheBundle\ConfigCache\Locale\ConfigCache', $definition->getClass());
    }

    /**
     * test for get method
     *
     * @dataProvider getIdProvider
     */
    public function testGetId($methodName, $internalMethodName, $suffix, $expected)
    {
        $register = $this->getRegisterMock(array('buildId', 'buildClassId'));
        $register
            ->expects($this->once())
            ->method($internalMethodName)
            ->with($suffix)
            ->willReturn($expected)
            ;
        $method   = new \ReflectionMethod($register, $methodName);
        $method->setAccessible(true);
        $this->assertSame($expected, $method->invoke($register));
    }

    public function getIdProvider()
    {
        $config = Yaml::parse(file_get_contents(__DIR__.'/../../Fixtures/get_id_provider.yml'));

        return $config['data'];
    }
}
