==============
AppScope Trait
==============

.. php:trait:: AppScopeTrait

Introduction
============

Typical software design will create the application scope. Most frameworks
relies on "static" properties, methods and classes. This does puts some
limitations on your implementation (you can't have multiple applications).

App Scope will pass the 'app' property into all the object that you're adding,
so that you know for sure which application you work with.

Properties
==========

.. php:attr:: app

    Always points to current Application object

.. php:attr:: max_name_length

    When using mechanism for ContainerTrait, they inherit name of the parent to
    generate unique name for a child. In a framework it makes sense if you have
    a unique identifiers for all the objects because this enables you to use
    them as session keys, get arguments, etc.

    Unfortunately if those keys become too long it may be a problem, so
    ContainerTrait contains a mechanism for auto-shortening the name based
    around max_name_length. The mechanism does only work if AppScopeTrait is
    used, $app property is set and has a max_name_length defined.
    Minimum value is 20.

.. php:attr:: unique_hashes

    As more names are shortened, the substituted part is being placed into
    this hash and the value contains the new key. This helps to avoid creating
    many sequential prefixes for the same character sequence.

Methods
=======

    None
