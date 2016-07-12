=============
Factory Trait
=============

.. php:trait:: FactoryTrait

Introduction
============

Properties
==========

    None

Methods
=======

.. php:meth:: factory($object, $defaults = [])

    Determine class name, call constructor.

.. php:meth:: normalizeClassName($name, $prefix = null)

    First normalize class name, then add specified prefix to
    class name if it's passed and not already added.
    Class name can have namespaces and they are treated prefectly.
    
    If object is passed as $name parameter, then same object is returned.
    
    Example:: normalizeClassName('User','Model') == 'Model_User';
