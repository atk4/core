<?php

class Object {
    use AppScopeTrait;
    use ContainerTrait;
    use DebugTrait;
    use DynamicMethodTrait;
    use HookTrait;
    use InitializerTrait;
    use ModelableTrait;
    use QuickExceptionTrait;
    use SessionTrait;
    use TrackableTrait;
    use FactoryTrait;

    function add($class, $args = null) {

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
