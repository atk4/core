<?php

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\ContainerTrait;
use atk4\core\DefinerTrait;
use atk4\core\Definition\iDefiner;
use atk4\core\DefinitionTrait;
use atk4\core\Exception;

/**
 * @coversDefaultClass  \atk4\core\DefinitionTrait
 */
class DefinitionTraitTest extends \atk4\core\PHPUnit7_AgileTestCase
{
    public $dir = __DIR__.'/definer_test/';

    /** @var iDefiner */
    public $mock;
    /**
     * this will throw an exception if there is some error in loading.
     */
    public function setUp()
    {
        $app = new DefinerMock();
        $app->readConfig($this->dir.DIRECTORY_SEPARATOR.'config.php','php-inline');

        $this->mock = new DefinitionChildMock();
        $app->add($this->mock);
    }

    public function testGetDefinition()
    {
        // test instance
        $result = $this->mock->getDefinition(\Psr\Log\LoggerInterface::class);
        $this->assertEquals(\Psr\Log\NullLogger::class, get_class($result));

        // test factory
        $result = $this->mock->getDefinition(DefinerFactoryMock::class);
        $this->assertEquals(DefinerFactoryMock::class, get_class($result));

        // test for default if not exists
        $result = $this->mock->getDefinition('MyLogger', new \Psr\Log\NullLogger());
        $this->assertEquals(\Psr\Log\NullLogger::class, get_class($result));

        // test for default if not exists with typecheck
        $result = $this->mock->getDefinition(\Psr\Log\NullLogger::class, new \Psr\Log\NullLogger(), true);
        $this->assertEquals(\Psr\Log\NullLogger::class, get_class($result));
    }

    /**
     * Test Exception when element not exists
     */
    public function testGetDefinitionExceptionNotExists()
    {
        $this->expectException(Exception::class);
        // test with Type check, will throw exception if fails
        $this->mock->getDefinition('NotExists');
    }

    /**
     * Test Exception when :
     *  - check_type is enabled
     *  - check if $path is a non existent FQCN = throw exception
     */
    public function testGetDefinitionException()
    {
        $this->expectException(Exception::class);
        // test with Type check, will throw exception if fails
        $this->mock->getDefinition('NotValidFQCNForTypeCheck',null,true);
    }

    /**
     * Test Exception when :
     *  - check_type is enabled
     *  - check if $path exists FQCN
     *  - return type is not equal to get_class($path) = throw exception
     */
    public function testGetDefinitionException2()
    {
        $this->expectException(Exception::class);
        // test with Type check, will throw exception if fails
        $this->mock->getDefinition(\DateTime::class,null,true);
    }

    /**
     * Test Instance and Factory Behaviour
     */
    public function testGetDefinition2()
    {
        /** @var DefinerInstanceMock $instance */
        $instance = $this->mock->getDefinition(DefinerInstanceMock::class);
        $this->assertEquals(0,$instance->count);
        $instance->increment();
        $this->assertEquals(1,$instance->count);

        // call again must give the same instance
        $instance = $this->mock->getDefinition(DefinerInstanceMock::class);
        $instance->increment();
        $this->assertEquals(2,$instance->count);

        $instance = $this->mock->getDefinition(DefinerInstanceMock::class);
        $instance->increment();
        $this->assertEquals(3,$instance->count);

        /** @var DefinerFactoryMock $factory */
        $factory = $this->mock->getDefinition(DefinerFactoryMock::class);
        $this->assertEquals(0,$factory->count);
        $factory->increment();
        $this->assertEquals(1,$factory->count);

        // call again must giuve a new instance
        $factory = $this->mock->getDefinition(DefinerFactoryMock::class);
        $this->assertEquals(0,$factory->count);
    }

    /**
     * Test via static method
     */
    public function testGetDefinition3()
    {
        /** @var DefinerMultipleArgumentMock $obj */
        $obj = $this->mock->getDefinition('TestStaticMethodInstance');
        $this->assertEquals([1,2,3],[$obj->a,$obj->b,$obj->c]);

        /** @var DefinerMultipleArgumentMock $obj */
        $obj = $this->mock->getDefinition('TestStaticMethodFactory');
        $this->assertEquals([1,2,3],[$obj->a,$obj->b,$obj->c]);
    }

    /**
     * Test Exception when element not exists
     */
    public function testGetDefinitionExceptionNoApp()
    {
        $this->mock = new DefinitionChildMock();

        $this->expectException(Exception::class);
        // test with Type check, will throw exception if fails
        $this->mock->getDefinition('NotExists');
    }
}

// @codingStandardsIgnoreStart
class DefinerMock implements iDefiner {
    use DefinerTrait;
    use AppScopeTrait;
    use ContainerTrait;

    /**
     * DefinerMock constructor.
     */
    public function __construct()
    {
        $this->app = $this;
    }
}

class DefinitionChildMock {
    use AppScopeTrait;
    use DefinitionTrait;
}

class DefinerInstanceMock {

   public $count = 0;

   public function increment()
   {
       $this->count++;
   }
}

class DefinerFactoryMock extends DefinerInstanceMock {
}

class DefinerMultipleArgumentMock {

    public $a = 0;
    public $b = 0;
    public $c = 0;

    public function __construct($a,$b,$c)
    {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
    }
}
// @codingStandardsIgnoreEnd