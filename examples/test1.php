<?php

declare(strict_types=1);

require '../vendor/autoload.php';

class MyParentObject
{
    use \Atk4\Core\ContainerTrait;
}

class MyChildClass
{
    use \Atk4\Core\TrackableTrait;
}

$parent = new MyParentObject();

$parent->add(new MyChildClass(), 'foo-bar');

var_dump($parent->getElement('foo-bar'));
