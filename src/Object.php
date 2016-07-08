<?php

use atk4\core;

class Object
{
    use core\AppScopeTrait;
    use core\ContainerTrait;
    use core\DebugTrait;
    use core\DynamicMethodTrait;
    use core\HookTrait;
    use core\InitializerTrait;
    use core\ModelableTrait;
    use core\QuickExceptionTrait;
    use core\SessionTrait;
    use core\TrackableTrait;
    use core\FactoryTrait;

    public function add($class, $args = null)
    {

        // Perform necessary loading and conver to object
        $object = $this->factory($class, $args);

        if ($object instanceof TrackableTrait) {
            $name = $object->getDesiredName();
        } else {
            $name = is_string($class) ? $class :
                is_object($class) ? get_class($class) :
                'o';
        }


        $this->_add_Container($object, $name);

        $object->init();
    }
}
