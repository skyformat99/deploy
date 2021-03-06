<?php

namespace App\Test;

use App\Action\Context;
use App\Model\Deployment;
use App\Model\Finder\EventFinder;
use App\Model\Project;
use App\Provider\ProviderInterface;
use PDO;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Ronanchilvers\Foundation\Config;
use RuntimeException;

/**
 * Base test case with utility methods
 *
 * @author Ronan Chilvers <ronan@thelittledot.com>
 */
class TestCase extends BaseTestCase
{
    /**
     * Get a mock PDO instance
     *
     * @return \PDO
     * @author Ronan Chilvers <ronan@thelittledot.com>
     */
    protected function mockPDO()
    {
        return $this->createMock(PDO::class);
    }

    /**
     * Get a mock logger
     *
     * @return Psr\Log\LoggerInterface
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    protected function mockLogger()
    {
        return $this->createMock(
            LoggerInterface::class
        );
    }

    /**
     * Get a mock container instance
     *
     * @return Psr\Container\ContainerInterface
     * @author Ronan Chilvers <ronan@thelittledot.com>
     */
    protected function mockContainer($data = null)
    {
        $builder = $this->getMockBuilder('Psr\Container\ContainerInterface')
                     ->setMethods(['get', 'has']);
        $mock = $builder->getMock();
        if (is_array($data)) {
            if (!array_key_exists(LoggerInterface::class, $data)) {
                $data[LoggerInterface::class] = $this->mockLogger();
            }
        } else {
            $data = [
                LoggerInterface::class => $this->mockLogger()
            ];
        }
        $mock->expects($this->any())
              ->method('has')
              ->willReturnCallback(function ($key) use ($data) {
                return array_key_exists($key, $data);
              });
        $mock->expects($this->any())
              ->method('get')
              ->willReturnCallback(function ($key) use ($data) {
                if (array_key_exists($key, $data)) {
                    return $data[$key];
                }

                throw new RuntimeException('Unexpected key passed to mock container : ' . $key);
              });

        return $mock;
    }

    /**
     * Get a mock project
     *
     * @return \App\Model\Project
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    protected function mockProject()
    {
        return $this->createMock(
            Project::class
        );
    }

    /**
     * Get a mock deployment
     *
     * @return \App\Model\Deployment
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    protected function mockDeployment()
    {
        return $this->createMock(
            Deployment::class
        );
    }

    /**
     * Get a mock settings object
     *
     * @return \Ronanchilvers\Foundation\Config
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    protected function mockSettings($data = null)
    {
        $mock = $this->createMock(
            Config::class
        );
        if (is_array($data)) {
            $callback = function ($key) use ($data) {
                if (array_key_exists($key, $data)) {
                    return $data[$key];
                }

                throw new RuntimeException('Unexpected key passed to config : ' . $key);
            };
            $mock->expects($this->any())
               ->method('get')
               ->willReturnCallback($callback);
        }

        return $mock;
    }

    /**
     * Get a mock config object
     *
     * @return \Ronanchilvers\Foundation\Config
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    protected function mockConfig($data = [])
    {
        $callback = function ($key) use ($data) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }

            throw new RuntimeException('Unexpected key passed to config : ' . $key);
        };
        $mock = $this->createMock(
            Config::class
        );
        $mock->expects($this->any())
            ->method('get')
            ->willReturnCallback($callback);

        return $mock;
    }

    /**
     * Get a mock context object
     *
     * @return \App\Action\Context
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    protected function mockContext($data = [])
    {
        $callback = function ($key) use ($data) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }

            throw new RuntimeException('Unexpected key passed to mock context : ' . $key);
        };

        $mock = $this->createMock(
            Context::class
        );
        $mock->expects($this->any())
             ->method('get')
             ->willReturnCallback($callback);
        $mock->expects($this->any())
             ->method('getOrThrow')
             ->willReturnCallback($callback);

        return $mock;
    }

    /**
     * Get a mock event finder object
     *
     * @return \App\Model\Finder\EventFinder
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    protected function mockEventFinder()
    {
        return $this->createMock(
            EventFinder::class
        );
    }

    /**
     * Get a mock provider
     *
     * @return App\Provider\ProviderInterface
     * @author Ronan Chilvers <ronan@d3r.com>
     */
    protected function mockProvider()
    {
        $mock = $this->createMock(
            ProviderInterface::class
        );

        return $mock;
    }

    /**
     * Get a protected method for later invocation
     *
     * @return \ReflectionMethod
     * @author Ronan Chilvers <ronan@thelittledot.com>
     */
    protected function getProtectedMethod($object, $method)
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * Call a protected method on a given object
     *
     * @param object $object
     * @param string $method
     * @param array $params
     * @return mixed
     * @author Ronan Chilvers <ronan@thelittledot.com>
     */
    protected function callProtectedMethod($object, $method, ...$params)
    {
        $method = $this->getProtectedMethod($object, $method);
        return $method->invokeArgs($object, $params);
    }

    /**
     * Get the value of a protected property
     *
     * @return mixed
     * @author Ronan Chilvers <ronan@thelittledot.com>
     */
    protected function getProtectedProperty($object, $property)
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}
