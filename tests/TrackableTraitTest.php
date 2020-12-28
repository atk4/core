<?php


namespace Atk4\Core\Tests;


use Atk4\Core\Exception;
use Atk4\Core\TrackableTrait;

class TrackableTraitTest extends \Atk4\Core\AtkPhpunit\TestCase
{
    private $trackable;

    protected function setUp(): void
    {
        $this->trackable = new class() {
            use TrackableTrait;
        };
    }

    public function testAssertNoDirectOwnerAssignmentException() {

        $this->expectException(Exception::class);

        $reflection = new \ReflectionClass($this->trackable);
        $property = $reflection->getProperty('owner');
        $property->setAccessible(true);
        $property->setValue($this->trackable, "fake type");
        $this->trackable->getOwner();
    }

    public function testAssertNoDirectOwnerAssignment() {

        $this->expectException(Exception::class);

        $reflection = new \ReflectionClass($this->trackable);
        $property = $reflection->getProperty('owner');
        $property->setAccessible(true);
        $property->setValue($this->trackable, "fake type");
        $this->trackable->getOwner();
    }

    public function testUnsetOwnerIfNotSet() {

        $this->trackable->setOwner(new \stdClass());
        $this->trackable->unsetOwner();

        $this->expectException(Exception::class);

        $this->trackable->unsetOwner();
    }

    public function testSetOwnerIfSet() {

        $this->trackable->setOwner(new \stdClass());

        $this->expectException(Exception::class);

        $this->trackable->setOwner(new \stdClass());
    }

    public function testDesiredNameOnAnonimous() {

        $this->assertEquals(
            "anonymous",
            $this->trackable->getDesiredName()
        );
    }
}