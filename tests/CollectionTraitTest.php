<?php

declare(strict_types=1);

namespace Atk4\Core\Tests;

use Atk4\Core\AppScopeTrait;
use Atk4\Core\ContainerTrait;
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

    public function testBasicWithApp(): void
    {
        $m = new CollectionMockWithApp();
        $m->setApp(new class() {
            public string $name = 'app';

            public int $maxNameLength = 40;
            /** @var array<string, string> */
            public array $uniqueNameHashes = [];
        });
        $m->name = 'form';

        /** @var FieldMockCustom $surnameField */
        $surnameField = $m->addField('surname', [FieldMockCustom::class]);

        self::assertSame('app', $surnameField->getApp()->name);

        self::assertSame('form-fields_surname', $surnameField->name);
        self::assertSame($m, $surnameField->getOwner());

        $longField = $m->addField('very-long-and-annoying-name-which-will-be-shortened', [FieldMockCustom::class]);
        self::assertSame(40, strlen($longField->name));

        $mWithContainerTrait = new class() extends CollectionMockWithApp {
            use ContainerTrait {
                _shorten as private __shorten;
            }

            protected function _shorten(string $ownerName, string $itemShortName, ?string $origItemName): string
            {
                return $this->__shorten($ownerName, 'X' . $itemShortName, $origItemName);
            }
        };
        $mWithContainerTrait->setApp($m->getApp());
        $mWithContainerTrait->name = 'form';

        /** @var FieldMockCustom $surnameField2 */
        $surnameField2 = $mWithContainerTrait->addField('surname', [FieldMockCustom::class]);

        self::assertSame('form-fields_Xsurname', $surnameField2->name);
        self::assertSame($mWithContainerTrait, $surnameField2->getOwner());
    }

    public function testCloneCollection(): void
    {
        $m = new CollectionMock();
        $m->addField('a', [FieldMock::class]);
        $m->addField('b', [FieldMockCustom::class]);

        $mCloned = clone $m;
        \Closure::bind(static fn () => $m->_cloneCollection('fields'), null, CollectionMock::class)();

        self::assertNotSame($m->getField('a'), $mCloned->getField('a'));
        self::assertNotSame($m->getField('b'), $mCloned->getField('b'));
        self::assertSame('b', $m->getField('b')->shortName); // @phpstan-ignore property.notFound
        self::assertSame('b', $mCloned->getField('b')->shortName); // @phpstan-ignore property.notFound
    }

    /**
     * Bad collection name.
     */
    public function testException1(): void
    {
        $m = new CollectionMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Collection does not exist');
        \Closure::bind(static fn () => $m->_addIntoCollection('foo', (object) [], ''), null, CollectionMock::class)(); // empty collection name
    }

    /**
     * Bad object name.
     */
    public function testException2(): void
    {
        $m = new CollectionMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Empty name is not supported');
        \Closure::bind(static fn () => $m->_addIntoCollection('', (object) [], 'fields'), null, CollectionMock::class)(); // empty object name
    }

    /**
     * Already existing object.
     */
    public function testException3(): void
    {
        $m = new CollectionMock();
        \Closure::bind(static fn () => $m->_addIntoCollection('foo', (object) [], 'fields'), null, CollectionMock::class)();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Element with the same name already exists in the collection');
        \Closure::bind(static fn () => $m->_addIntoCollection('foo', (object) [], 'fields'), null, CollectionMock::class)(); // already exists
    }

    /**
     * Cannot get non existent object.
     */
    public function testException4(): void
    {
        $m = new CollectionMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Element is not in the collection');
        \Closure::bind(static fn () => $m->_getFromCollection('dont_exist', 'fields'), null, CollectionMock::class)(); // does not exist
    }

    /**
     * Cannot remove non existent object.
     */
    public function testException5(): void
    {
        $m = new CollectionMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Element is not in the collection');
        \Closure::bind(static fn () => $m->_removeFromCollection('dont_exist', 'fields'), null, CollectionMock::class)(); // does not exist
    }

    public function testParentInitNotCalledException(): void
    {
        $m = new CollectionMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Object was not initialized');
        $m->addField('foo', new class() extends FieldMock {
            use InitializerTrait;

            protected function init(): void {}
        });
    }

    public function testExceptionInInitMustNotAdd(): void
    {
        $m = new CollectionMock();

        $e = null;
        try {
            $m->addField('foo', new class() extends FieldMock {
                use InitializerTrait;

                protected function init(): void
                {
                    throw new Exception('from init');
                }
            });
        } catch (Exception $e) {
        }
        self::assertSame('from init', $e->getMessage());

        self::assertFalse($m->hasField('foo'));
        $m->addField('foo', new FieldMock());
        self::assertTrue($m->hasField('foo'));
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
