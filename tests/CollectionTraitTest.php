<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core;
use Atk4\Core\Phpunit\TestCase;

class CollectionTraitTest extends TestCase
{
    public function testBasic(): void
    {
        $m = new CollectionMock();
        $m->addField('name');

        static::assertTrue($m->hasField('name'));

        $m->addField('surname', [FieldMockCustom::class]);

        static::assertSame(FieldMockCustom::class, get_class($m->getField('surname')));
        /** @var FieldMockCustom $field */
        $field = $m->getField('surname');
        static::assertTrue($field->var);

        $m->removeField('name');
        static::assertFalse($m->hasField('name'));
    }

    /**
     * Test Trackable and AppScope.
     */
    public function testBasicWithApp(): void
    {
        $m = new CollectionMockWithApp();
        $m->setApp(new class() {
            /** @var string */
            public $name = 'app';
            /** @var int */
            public $maxNameLength = 40;
            /** @var array */
            public $uniqueNameHashes = [];
        });
        $m->name = 'form';

        /** @var FieldMockCustom $surnameField */
        $surnameField = $m->addField('surname', [FieldMockCustom::class]);

        static::assertSame('app', $surnameField->getApp()->name);

        static::assertSame('form-fields_surname', $surnameField->name);
        static::assertSame($m, $surnameField->getOwner());

        $longField = $m->addField('very-long-and-annoying-name-which-will-be-shortened', [FieldMockCustom::class]);
        static::assertSame(40, strlen($longField->name));
    }

    /**
     * Bad collection name.
     */
    public function testException1(): void
    {
        $this->expectException(Core\Exception::class);
        $m = new CollectionMock();
        $m->_addIntoCollection('foo', (object) [], ''); // empty collection name
    }

    /**
     * Bad object name.
     */
    public function testException2(): void
    {
        $this->expectException(Core\Exception::class);
        $m = new CollectionMock();
        $m->_addIntoCollection('', (object) [], 'fields'); // empty object name
    }

    /**
     * Already existing object.
     */
    public function testException3(): void
    {
        $this->expectException(Core\Exception::class);
        $m = new CollectionMock();
        $m->_addIntoCollection('foo', (object) [], 'fields');
        $m->_addIntoCollection('foo', (object) [], 'fields'); // already exists
    }

    /**
     * Cannot remove non existant object.
     */
    public function testException4(): void
    {
        $this->expectException(Core\Exception::class);
        $m = new CollectionMock();
        $m->_removeFromCollection('dont_exist', 'fields'); // do not exist
    }

    /**
     * Cannot get non existant object.
     */
    public function testException5(): void
    {
        $this->expectException(Core\Exception::class);
        $m = new CollectionMock();
        $m->_getFromCollection('dont_exist', 'fields'); // do not exist
    }

    /**
     * Cannot get non existant object.
     */
    public function testException6(): void
    {
        $this->expectException(Core\Exception::class);
        $m = new CollectionMock();
        $m->addField('test', new class() {
            use Core\DiContainerTrait;
            use Core\InitializerTrait;

            /** @var string */
            public $name;

            protected function init(): void
            {
            }
        });
    }

    public function testClone(): void
    {
        $m = new CollectionMock();
        $m->addField('name');
        $m->addField('surname', [FieldMockCustom::class]);

        $c = clone $m;
        static::assertTrue($c->hasField('name'));
        /** @var FieldMockCustom $field */
        $field = $c->getField('surname');
        static::assertSame(FieldMockCustom::class, get_class($field));
        static::assertTrue($field->var);
    }
}

class CollectionMockWithApp extends CollectionMock
{
    use Core\AppScopeTrait;
    use Core\NameTrait;
    use Core\TrackableTrait;
}
