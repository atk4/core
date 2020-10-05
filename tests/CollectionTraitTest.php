<?php

declare(strict_types=1);

namespace atk4\core\tests;

use atk4\core;
use atk4\core\AtkPhpunit;

/**
 * @coversDefaultClass \atk4\core\ContainerTrait
 */
class CollectionTraitTest extends AtkPhpunit\TestCase
{
    /**
     * Test constructor.
     */
    public function testBasic()
    {
        try {
            $m = new CollectionMock();
            $m->addField('name');

            $this->assertTrue($m->hasField('name'));

            $m->addField('surname', [CustomFieldMock::class]);

            $this->assertSame(CustomFieldMock::class, get_class($m->getField('surname')));
            $this->assertTrue($m->getField('surname')->var);

            $m->removeField('name');
            $this->assertFalse($m->hasField('name'));
        } catch (core\Exception $e) {
            echo $e->getColorfulText();

            throw $e;
        }
    }

    /**
     * Test Trackable and AppScope.
     */
    public function testBasicWithApp()
    {
        try {
            $m = new CollectionMockWithApp();
            $m->setApp(new class() {
                public $name = 'app';
                public $max_name_length = 20;
            });
            $m->name = 'form';

            $surname = $m->addField('surname', [CustomFieldMock::class]);

            $this->assertSame('app', $surname->getApp()->name);

            $this->assertSame('form-fields_surname', $surname->name);
            $this->assertSame($surname->getOwner(), $m);

            $long = $m->addField('very-long-and-annoying-name-which-will-be-shortened', [CustomFieldMock::class]);
            $this->assertLessThan(21, strlen($long->name));
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
        $m = new CollectionMock();
        $m->_addIntoCollection('foo', (object) [], ''); // empty collection name
    }

    /**
     * Bad object name.
     */
    public function testException2()
    {
        $this->expectException(core\Exception::class);
        $m = new CollectionMock();
        $m->_addIntoCollection('', (object) [], 'fields'); // empty object name
    }

    /**
     * Already existing object.
     */
    public function testException3()
    {
        $this->expectException(core\Exception::class);
        $m = new CollectionMock();
        $m->_addIntoCollection('foo', (object) [], 'fields');
        $m->_addIntoCollection('foo', (object) [], 'fields'); // already exists
    }

    /**
     * Can not remove non existant object.
     */
    public function testException4()
    {
        $this->expectException(core\Exception::class);
        $m = new CollectionMock();
        $m->_removeFromCollection('dont_exist', 'fields'); // do not exist
    }

    /**
     * Can not get non existant object.
     */
    public function testException5()
    {
        $this->expectException(core\Exception::class);
        $m = new CollectionMock();
        $m->_getFromCollection('dont_exist', 'fields'); // do not exist
    }

    /**
     * Can not get non existant object.
     */
    public function testException6()
    {
        $this->expectException(core\Exception::class);
        $m = new CollectionMock();
        $m->addField('test', new class() {
            use core\DiContainerTrait;
            use core\InitializerTrait;
            public $name;

            protected function init(): void
            {
            }
        });
    }

    public function testClone()
    {
        try {
            $m = new CollectionMock();
            $m->addField('name');
            $m->addField('surname', [CustomFieldMock::class]);

            $c = clone $m;
            $this->assertTrue($c->hasField('name'));
            $this->assertSame(CustomFieldMock::class, get_class($c->getField('surname')));
            $this->assertTrue($c->getField('surname')->var);
        } catch (core\Exception $e) {
            echo $e->getColorfulText();

            throw $e;
        }
    }
}

/**
 * Adds support for apptrait and trackable.
 */
class CollectionMockWithApp extends CollectionMock
{
    use core\TrackableTrait;
    use core\AppScopeTrait;
    use core\FactoryTrait;
}
