<?php

require '../vendor/autoload.php';

class MyClass
{
    use \atk4\core\HookTrait;

    public function doWork()
    {
        $this->hook('beforeWork');

        echo "Doing work\n";

        $this->hook('afterWork');
    }
}

$c = new MyClass();
$c->addHook('afterWork', function () {
    echo "HOOKed on work\n";
});
$c->doWork();
