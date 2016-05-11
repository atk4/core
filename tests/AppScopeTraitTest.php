<?php
namespace atk4\core\tests;

use atk4\core\AppScopeTrait;

/**
 * @coversDefaultClass \atk4\data\Model
 */
class AppScopeTraitTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test constructor
     *
     */
    public function testConstruct()
    {
        $m = new AppScopeMock();
        $m->app="myapp";
    }

}

class AppScopeMock {
    use AppScopeTrait;
}
