====================
Dynamic Method Trait
====================

.. php:trait:: DynamicMethodTrait

Introduction
============

.. php:method:: addMethod($name, $callback)

Adds ability to add methods into objects dynamically. That's like a "trait"
feature of a PHP, but implemented in run-time::

    $object->addMethod('test', function($o, $args){ echo 'hello, '.$args[0]; } );
    $object->test('world');

Global Methods
==============

If object has application scope :php:trait:`AppScopeTrait` and the application
implements :php:trait:`HookTrait` then executing $object->test() will also
attempt look for globally-registered method inside the application::

    $object->app->addGlobalMethod('test', function($app, $o, $args){
        echo 'hello, '.$args[0];
    });

    $object->test('world');

Of course calling test() on the other object afterwards will trigger same
global method.

If you attempt to register same method multiple times you will recevie
an exception.

Dynamic Method Arguments
========================
When calling dynamic methods the arguments are passed to the method,
however an extra argument will be prepended equal to the object
that this method was defined on::

    $m->addMethod('sum', function($m, $a, $b){ return $a+$b; });

    echo $m->sum(3,5);
    // 8

.. php:method:: hasMethod

    will respond with true if method is defined either in the object
    or globally.
