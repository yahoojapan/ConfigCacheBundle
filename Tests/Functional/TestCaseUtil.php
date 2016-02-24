<?php

/*
 * This file is part of the ConfigCacheBundle package.
 *
 * Copyright (c) 2015 Yahoo Japan Corporation
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YahooJapan\ConfigCacheBundle\Tests\Functional;

/**
 * TestCase utilities.
 */
class TestCaseUtil
{
    protected $phpunit;

    /**
     * Constructor.
     *
     * @param \PHPUnit_Framework_TestCase $phpunit
     */
    public function __construct(\PHPUnit_Framework_TestCase $phpunit)
    {
        $this->phpunit = $phpunit;
    }

    /**
     * Gets a property by instance, property name.
     *
     * @param object $instance
     * @param string $name
     *
     * @return mixed
     */
    public function getProperty($instance, $name)
    {
        $property = new \ReflectionProperty($instance, $name);
        $property->setAccessible(true);

        return $property->getValue($instance);
    }

    /**
     * Sets a property by instance, property name, and value.
     *
     * @param object $instance
     * @param string $name
     * @param string $value
     *
     * @return TestCaseUtil
     */
    public function setProperty($instance, $name, $value)
    {
        $property = new \ReflectionProperty($instance, $name);
        $property->setAccessible(true);
        $property->setValue($instance, $value);

        return $this;
    }

    /**
     * Invokes the reflected method.
     *
     * After the third argument, a variable number of parameters.
     *
     * @param object $instance
     * @param string $name
     *
     * @return mixed
     */
    public function invoke($instance, $name)
    {
        $args = func_get_args();
        unset($args[0], $args[1]);

        return $this->invokeArgs($instance, $name, $args);
    }

    /**
     * Invokes the reflected method by args.
     *
     * @param object $instance
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     */
    public function invokeArgs($instance, $name, array $args = array())
    {
        $method = new \ReflectionMethod($instance, $name);
        $method->setAccessible(true);

        return $method->invokeArgs($instance, $args);
    }

    /**
     * Creates a mock.
     *
     * @param string     $className to mock class name
     * @param array|null $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function createMock($className, array $methods = null)
    {
        return $this->phpunit->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock()
            ;
    }

    /**
     * Creates an abstract class mock.
     *
     * @param string     $className to mock abstract class name
     * @param array|null $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function createAbstractMock($className, array $methods = null)
    {
        $builder = $this->phpunit->getMockBuilder($className)
            ->disableOriginalConstructor()
            ;
        if (!is_null($methods)) {
            $builder->setMethods($methods);
        }

        return $builder->getMockForAbstractClass();
    }

    /**
     * Creates a mock by interface.
     *
     * Need to get all methods of interface for mocking.
     *
     * @param string $interfaceName to mock interface name
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function createInterfaceMock($interfaceName)
    {
        return $this->phpunit->getMockBuilder($interfaceName)
            ->setMethods($this->getMethods($interfaceName))
            ->getMock()
            ;
    }

    /**
     * Gets method names by class or interface name.
     *
     * @param string $name class or interface name
     *
     * @return array method names
     */
    protected function getMethods($name)
    {
        $methods = array();
        $class   = new \ReflectionClass($name);
        foreach ($class->getMethods() as $method) {
            $methods[] = $method->getName();
        }

        return $methods;
    }
}
