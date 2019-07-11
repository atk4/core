<?php

use atk4\core\AppScopeTrait;
use atk4\core\ContainerTrait;
use atk4\core\TranslatableTrait;

require '../vendor/autoload.php';

class App {
    use AppScopeTrait;
    use ContainerTrait;

    public function __construct()
    {
        $this->app = $this;
    }
}

class TranslatableChild
{
    use AppScopeTrait;
    use TranslatableTrait;
}

$app = new App();
$child = new TranslatableChild();
$app->add($child);

var_dump($child->_('translate with %name%', ['%name%' => 'atk']));

var_dump($child->_('there is one apple|there are %count% apples', ['%count%' => 1]));

var_dump($child->_('there is one apple|there are %count% apples', ['%count%' => 2]));

var_dump($child->_('there is one %counted_name%|there are %count% %counted_name%', [
    '%count%' => 2,
    '%counted_name%' => $child->_('apple|apples', ['%count%' => 2])
]));

var_dump($child->_('there is one %counted_name%|there are %count% %counted_name%', [
    '%count%' => 1,
    '%counted_name%' => $child->_('apple|apples', ['%count%' => 1])
]));
