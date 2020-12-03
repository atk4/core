<?php

declare(strict_types=1);

require '../vendor/autoload.php';

class MyClass
{
    use \Atk4\Core\HookTrait;

    public function doWork()
    {
        $this->hook('beforeWork');

        echo "Doing work\n";

        $this->hook('afterWork');
    }
}

$c = new MyClass();
$c->onHook('afterWork', function () {
    echo "HOOKed on work\n";
});
$c->doWork();
