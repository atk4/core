<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\AppScopeTrait;
use Atk4\Core\DiContainerTrait;
use Atk4\Core\Exception;
use Atk4\Core\InitializerTrait;
use Atk4\Core\NameTrait;
use Atk4\Core\Phpunit\TestCase;
use Atk4\Core\TrackableTrait;

class CollectionTraitTest extends TestCase
{
    public function testBasic(): void
    {
        $m = new CollectionMock();
        $m->addField('name');

        self::assertTrue($m->hasField('name'));

        $m->addField('surname', [FieldMockCustom::class]);

        self::assertSame(FieldMockCustom::class, get_class($m->getField('surname')));
        /** @var FieldMockCustom $field */
        $field = $m->getField('surname');
        self::assertTrue($field->var);

        $m->removeField('name');
        self::assertFalse($m->hasField('name'));
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
            /** @var array<string, string> */
            public $uniqueNameHashes = [];
        });
        $m->name = 'form';

        /** @var FieldMockCustom $surnameField */
        $surnameField = $m->addField('surname', [FieldMockCustom::class]);

        self::assertSame('app', $surnameField->getApp()->name);

        self::assertSame('form-fields_surname', $surnameField->name);
        self::assertSame($m, $surnameField->getOwner());

        $longField = $m->addField('very-long-and-annoying-name-which-will-be-shortened', [FieldMockCustom::class]);
        self::assertSame(40, strlen($longField->name));
    }

    /**
     * Bad collection name.
     */
    public function testException1(): void
    {
        $this->expectException(Exception::class);
        $m = new CollectionMock();
        $m->_addIntoCollection('foo', (object) [], ''); // empty collection name
    }

    /**
     * Bad object name.
     */
    public function testException2(): void
    {
        $this->expectException(Exception::class);
        $m = new CollectionMock();
        $m->_addIntoCollection('', (object) [], 'fields'); // empty object name
    }

    /**
     * Already existing object.
     */
    public function testException3(): void
    {
        $this->expectException(Exception::class);
        $m = new CollectionMock();
        $m->_addIntoCollection('foo', (object) [], 'fields');
        $m->_addIntoCollection('foo', (object) [], 'fields'); // already exists
    }

    /**
     * Cannot remove non existent object.
     */
    public function testException4(): void
    {
        $this->expectException(Exception::class);
        $m = new CollectionMock();
        $m->_removeFromCollection('dont_exist', 'fields'); // do not exist
    }

    /**
     * Cannot get non existent object.
     */
    public function testException5(): void
    {
        $this->expectException(Exception::class);
        $m = new CollectionMock();
        $m->_getFromCollection('dont_exist', 'fields'); // do not exist
    }

    /**
     * Cannot get non existent object.
     */
    public function testException6(): void
    {
        $this->expectException(Exception::class);
        $m = new CollectionMock();
        $m->addField('test', new class() {
            use DiContainerTrait;
            use InitializerTrait;

            /** @var string */
            public $name;

            protected function init(): void {}
        });
    }

    public function testClone(): void
    {
        $m = new CollectionMock();
        $m->addField('name');
        $m->addField('surname', [FieldMockCustom::class]);

        $c = clone $m;
        self::assertTrue($c->hasField('name'));
        /** @var FieldMockCustom $field */
        $field = $c->getField('surname');
        self::assertSame(FieldMockCustom::class, get_class($field));
        self::assertTrue($field->var);
    }
}

class CollectionMockWithApp extends CollectionMock
{
    use AppScopeTrait;
    use NameTrait;
    use TrackableTrait;
}
