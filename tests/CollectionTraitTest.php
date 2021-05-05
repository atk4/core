<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core;
use Atk4\Core\AtkPhpunit;

/**
 * @coversDefaultClass \Atk4\Core\ContainerTrait
 */
class CollectionTraitTest extends AtkPhpunit\TestCase
{
    public function testBasic()
    {
        $m = new CollectionMock();
        $m->addField('name');

        $this->assertTrue($m->hasField('name'));

        $m->addField('surname', [CustomFieldMock::class]);

        $this->assertSame(CustomFieldMock::class, get_class($m->getField('surname')));
        $this->assertTrue($m->getField('surname')->var);

        $m->removeField('name');
        $this->assertFalse($m->hasField('name'));
    }

    /**
     * Test Trackable and AppScope.
     */
    public function testBasicWithApp()
    {
        $m = new CollectionMockWithApp();
        $m->setApp(new class() {
            public $name = 'app';
            public $max_name_length = 40;
        });
        $m->name = 'form';

        $surname = $m->addField('surname', [CustomFieldMock::class]);

        $this->assertSame('app', $surname->getApp()->name);

        $this->assertSame('form-fields_surname', $surname->name);
        $this->assertSame($surname->getOwner(), $m);

        $long = $m->addField('very-long-and-annoying-name-which-will-be-shortened', [CustomFieldMock::class]);
        $this->assertSame(40, strlen($long->name));
    }

    /**
     * Bad collection name.
     */
    public function testException1()
    {
        $this->expectException(Core\Exception::class);
        $m = new CollectionMock();
        $m->_addIntoCollection('foo', (object) [], ''); // empty collection name
    }

    /**
     * Bad object name.
     */
    public function testException2()
    {
        $this->expectException(Core\Exception::class);
        $m = new CollectionMock();
        $m->_addIntoCollection('', (object) [], 'fields'); // empty object name
    }

    /**
     * Already existing object.
     */
    public function testException3()
    {
        $this->expectException(Core\Exception::class);
        $m = new CollectionMock();
        $m->_addIntoCollection('foo', (object) [], 'fields');
        $m->_addIntoCollection('foo', (object) [], 'fields'); // already exists
    }

    /**
     * Can not remove non existant object.
     */
    public function testException4()
    {
        $this->expectException(Core\Exception::class);
        $m = new CollectionMock();
        $m->_removeFromCollection('dont_exist', 'fields'); // do not exist
    }

    /**
     * Can not get non existant object.
     */
    public function testException5()
    {
        $this->expectException(Core\Exception::class);
        $m = new CollectionMock();
        $m->_getFromCollection('dont_exist', 'fields'); // do not exist
    }

    /**
     * Can not get non existant object.
     */
    public function testException6()
    {
        $this->expectException(Core\Exception::class);
        $m = new CollectionMock();
        $m->addField('test', new class() {
            use Core\DiContainerTrait;
            use Core\InitializerTrait;
            public $name;

            protected function init(): void
            {
            }
        });
    }

    public function testClone()
    {
        $m = new CollectionMock();
        $m->addField('name');
        $m->addField('surname', [CustomFieldMock::class]);

        $c = clone $m;
        $this->assertTrue($c->hasField('name'));
        $this->assertSame(CustomFieldMock::class, get_class($c->getField('surname')));
        $this->assertTrue($c->getField('surname')->var);
    }
}

/**
 * Adds support for apptrait and trackable.
 */
class CollectionMockWithApp extends CollectionMock
{
    use Core\AppScopeTrait;
    use Core\TrackableTrait;
}
