<?php

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\ContainerTrait;
use atk4\core\Exception;
use atk4\core\ServiceDefinition\iDefiner;
use atk4\core\ServiceLocatorTrait;

/**
 * @coversDefaultClass  \atk4\core\ServiceLocatorTrait
 */
class ServiceLocatorTraitTest extends \atk4\core\PHPUnit7_AgileTestCase
{
    public $dir = __DIR__.'/servicelocator_test/';

    /** @var ServiceChildMock */
    public $mock;

    /**
     * this will throw an exception if there is some error in loading.
     */
    public function setUp() : void
    {
        $app = new AppServiceMock();
        $app->readConfig($this->dir.DIRECTORY_SEPARATOR.'config.php', 'php-inline');

        $this->mock = new ServiceChildMock();
        $app->add($this->mock);
    }

    /**
     * @throws Exception
     */
    public function testGetService()
    {
        // test instance
        $result = $this->mock->getService(\Psr\Log\LoggerInterface::class);
        $this->assertEquals(\Psr\Log\NullLogger::class, get_class($result));

        // test factory
        $result = $this->mock->getService(ServiceFactoryMock::class);
        $this->assertEquals(ServiceFactoryMock::class, get_class($result));

        // test for default if not exists with typecheck
        $result = $this->mock->getService(\Psr\Log\NullLogger::class, new \Psr\Log\NullLogger());
        $this->assertEquals(\Psr\Log\NullLogger::class, get_class($result));
    }

    /**
     * Test Exception when element not exists.
     *
     * @throws Exception
     */
    public function testGetServiceExceptionNotExists()
    {
        $this->expectException(Exception::class);
        // test with Type check, will throw exception if fails
        $this->mock->getService('NotExists');
    }

    /**
     * Test Exception when :
     *  - check_type is enabled
     *  - check if $path is a non existent FQCN = throw exception.
     *
     * @throws Exception
     */
    public function testGetServiceException()
    {
        $this->expectException(Exception::class);
        // test with Type check, will throw exception if fails
        $this->mock->getService('NotValidFQCNForTypeCheck', null);
    }

    /**
     * Test Exception when :
     *  - check_type is enabled
     *  - check if $path exists FQCN
     *  - return type is not equal to get_class($path) = throw exception.
     *
     * @throws Exception
     */
    public function testGetServiceException2()
    {
        $this->expectException(Exception::class);
        // test with Type check, will throw exception if fails
        $this->mock->getService(\DateTime::class, null);
    }

    /**
     * Test Instance and Factory Behaviour.
     *
     * @throws Exception
     */
    public function testGetService2()
    {
        /** @var ServiceInstanceMock $instance */
        $instance = $this->mock->getService(ServiceInstanceMock::class);
        $this->assertEquals(0, $instance->count);
        $instance->increment();
        $this->assertEquals(1, $instance->count);

        // call again must give the same instance
        $instance = $this->mock->getService(ServiceInstanceMock::class);
        $instance->increment();
        $this->assertEquals(2, $instance->count);

        $instance = $this->mock->getService(ServiceInstanceMock::class);
        $instance->increment();
        $this->assertEquals(3, $instance->count);

        /** @var ServiceFactoryMock $factory */
        $factory = $this->mock->getService(ServiceFactoryMock::class);
        $this->assertEquals(0, $factory->count);
        $factory->increment();
        $this->assertEquals(1, $factory->count);

        // call again must giuve a new instance
        $factory = $this->mock->getService(ServiceFactoryMock::class);
        $this->assertEquals(0, $factory->count);
    }

    /**
     * Test via static method.
     */
    public function testGetService3()
    {
        /** @var ServiceMultipleArgumentMock $obj */
        $obj = $this->mock->getService(InstanceServiceMultipleArgumentMock::class);
        $this->assertEquals([1, 2, 3], [$obj->a, $obj->b, $obj->c]);

        /** @var ServiceMultipleArgumentMock $obj */
        $obj = $this->mock->getService(FactoryServiceMultipleArgumentMock::class);
        $this->assertEquals([1, 2, 3], [$obj->a, $obj->b, $obj->c]);
    }

    /**
     * Test Exception when element not exists.
     */
    public function testGetServiceExceptionNoApp()
    {
        $this->mock = new ServiceChildMock();

        $this->expectException(Exception::class);
        // test with Type check, will throw exception if fails
        $this->mock->getService('NotExists');
    }
}

// @codingStandardsIgnoreStart
class AppServiceMock implements iDefiner
{
    use AppScopeTrait;
    use ContainerTrait;
    use ServiceLocatorTrait;

    /**
     * DefinerMock constructor.
     */
    public function __construct()
    {
        $this->app = $this;
    }
}

class ServiceChildMock
{
    use AppScopeTrait;
    use ServiceLocatorTrait;
}

class ServiceInstanceMock
{
    public $count = 0;

    public function increment()
    {
       $this->count++;
    }
}

class ServiceFactoryMock extends ServiceInstanceMock
{
}

class ServiceMultipleArgumentMock
{
    public $a = 0;
    public $b = 0;
    public $c = 0;

    public function __construct($a, $b, $c)
    {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
    }
}

class InstanceServiceMultipleArgumentMock extends ServiceMultipleArgumentMock
{
}

class FactoryServiceMultipleArgumentMock extends ServiceMultipleArgumentMock
{
}
// @codingStandardsIgnoreEnd