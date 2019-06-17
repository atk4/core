<?php

namespace atk4\core\tests;

use atk4\core;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \atk4\core\ContainerTrait
 */
class MultiContainerTraitTest extends TestCase
{
    /**
     * Test constructor.
     * @throws core\Exception
     */
    public function testBasic()
    {
        try {
            $m = new MultiContainerMock();
            $m->addField('name');

            $this->assertNotEmpty($m->hasField('name'));


            $m->addField('surname', ['CustomFieldMock']);

            $this->assertEquals(CustomFieldMock::class, get_class($m->hasField('surname')));
            $this->assertTrue($m->getField('surname')->var);

            $m->removeField('name');
            $this->assertEmpty($m->hasField('name'));
        } catch (core\Exception $e) {
            echo $e->getColorfulText();
            throw $e;
        }
    }

}
