<?php

require '../vendor/autoload.php';

class MyParentObject
{
    use \atk4\core\ContainerTrait;
}

class MyChildClass
{
    use \atk4\core\TrackableTrait;
}

$parent = new MyParentObject();

$parent->add(new MyChildClass(), 'foo-bar');

var_dump($parent->getElement('foo-bar'));
