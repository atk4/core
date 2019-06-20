<?php

namespace atk4\core\tests;

use atk4\core\AppScopeTrait;
use atk4\core\ContainerTrait;
use atk4\core\DefinerTrait;
use atk4\core\Definition\iDefiner;
use atk4\core\DefinitionTrait;
use PHPUnit\Framework\TestCase;
/**
 * @coversDefaultClass \atk4\core\DefinerTrait
 */
class DefinerTraitTest extends TestCase
{
    public $dir = __DIR__.'/definer_test/';

    /** @var iDefiner */
    public $mock;
    /**
     * this will throw an exception if there is some error in loading.
     */
    public function setUp()
    {
        $this->mock = new DefinerMock();
        $this->mock->readConfig($this->dir.DIRECTORY_SEPARATOR.'config.php','php-inline');
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
    }

    /**
     * @expectedException \atk4\core\Exception
     */
    public function testGetDefinition_exception()
    {
        // test with Type check, will throw exception if fails
        $this->mock->getDefinition('NotValidFQCNForTypeCheck',null,true);
    }

    public function testDefinitionBehaviour()
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

    public function testDefinitionViaStaticMethod()
    {
        /** @var DefinerMultipleArgumentMock $obj */
        $obj = $this->mock->getDefinition('TestStaticMethodInstance');
        $this->assertEquals([1,2,3],[$obj->a,$obj->b,$obj->c]);

        /** @var DefinerMultipleArgumentMock $obj */
        $obj = $this->mock->getDefinition('TestStaticMethodFactory');
        $this->assertEquals([1,2,3],[$obj->a,$obj->b,$obj->c]);
    }

    public function testCallsFromAddChild()
    {
        $child = new DefinitionChildMock();
        $this->mock->add($child);

        /** @var DefinerInstanceMock $instance */
        $instance = $child->getDefinition(DefinerInstanceMock::class);
        $this->assertEquals(0,$instance->count);
        $instance->increment();
        $this->assertEquals(1,$instance->count);

        // call again must give the same instance
        $instance = $child->getDefinition(DefinerInstanceMock::class);
        $instance->increment();
        $this->assertEquals(2,$instance->count);

        // test for default if not exists
        $result = $child->getDefinition('MyLogger', new \Psr\Log\NullLogger());
        $this->assertEquals(\Psr\Log\NullLogger::class, get_class($result));
    }
}

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