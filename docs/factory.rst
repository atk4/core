=============
Factory Trait
=============

.. php:trait:: FactoryTrait

Introduction
============

    This trait can be used to dynamically create new objects by their class
    names. It also contains method for class name normalization.

Properties
==========

    None

Methods
=======

.. php:meth:: factory($object, $defaults = [])

    Creates and returns new object.
    If object is passed as $object parameter, then same object is returned.

.. php:meth:: normalizeClassName($name, $prefix = null)

    First normalize class name, then add specified prefix to
    class name if it's passed and not already added.
    Class name can contain namespace.
    
    If object is passed as $name parameter, then same object is returned.
    
    Example:: normalizeClassName('User','Model') == 'Model_User';

    If object also has an "AppScopeTrait" and if your application object contains
    a method 'normalizeClassName' then we will execute that. A typical use would 
    be if your application defines various routes how to route strings into
    class names permitting this syntax::

        $obj = $this->add('MyClass');


    Application's normalizeClassName can prepend namespace which will then be
    passed into factory() method for instatination.

