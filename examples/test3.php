<?php

require '../vendor/autoload.php';

class MyClass
{
    use \atk4\core\DynamicMethodTrait;
    use \atk4\core\HookTrait;
}

$c = new MyClass();

$c->addMethod('mymethod', function ($c, $a, $b) {
    return $a + $b;
});

echo $c->mymethod(2, 3)."\n";
