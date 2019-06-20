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
     *
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

    /**
     * Bad collection name.
     */
    public function testException1()
    {
        $this->expectException(core\Exception::class);
        $m = new MultiContainerMock();
        $m->_addIntoCollection('foo', (object) [], ''); // empty collection name
    }

    /**
     * Bad object name.
     */
    public function testException2()
    {
        $this->expectException(core\Exception::class);
        $m = new MultiContainerMock();
        $m->_addIntoCollection('', (object) [], 'fields'); // empty object name
    }

    /**
     * Already existing object.
     */
    public function testException3()
    {
        $this->expectException(core\Exception::class);
        $m = new MultiContainerMock();
        $m->_addIntoCollection('foo', (object) [], 'fields');
        $m->_addIntoCollection('foo', (object) [], 'fields'); // already exists
    }

    /**
     * Can not remove non existant object.
     */
    public function testException4()
    {
        $this->expectException(core\Exception::class);
        $m = new MultiContainerMock();
        $m->_removeFromCollection('dont_exist', 'fields'); // do not exist
    }

    /**
     * Can not get non existant object.
     */
    public function testException5()
    {
        $this->expectException(core\Exception::class);
        $m = new MultiContainerMock();
        $m->_getFromCollection('dont_exist', 'fields'); // do not exist
    }
}
