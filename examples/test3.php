<?php

declare(strict_types=1);

require '../vendor/autoload.php';

class MyClass2
{
    use \atk4\core\DynamicMethodTrait;
    use \atk4\core\HookTrait;
}

$c = new MyClass2();

$c->addMethod('mymethod', function ($c, $a, $b) {
    return $a + $b;
});

// @phpstan-ignore-next-line
echo $c->mymethod(2, 3) . "\n";
