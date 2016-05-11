<?php
namespace atk4\core\tests;

use atk4\core\DynamicMethodTrait;

/**
 * @coversDefaultClass \atk4\data\Model
 */
class DynamicMethodTraitTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test constructor
     *
     */
    public function testConstruct()
    {
        $m = new DynamicMethodMock();
        $m->addMethod('test', function(){ return 'ok'; });

        $m->tryCall('test');

        $m->hasMethod('test');

        $m->removeMethod('test');
    }

}

class DynamicMethodMock {
    use DynamicMethodTrait;
}
