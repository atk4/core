===========
Debug Trait
===========

.. php:trait:: DebugTrait

Introduction
============

DebugTrait adds a few essential methods to your object that can be very handy when
trying to find a problem deep in the framework. DebugTrait has the following features

- ability to switch debug only for specific objects or system-wide
- can log basic error and separatly additional info (objects)
- override debug reporting in the API

Additionally the following features are available:

debugTraceChange
----------------

This method can help you find situations when a certain code is called
multiple times and when it shouldn't. When called first time it will
remember "trace" which is used to arrive at this point. Second time
it will compare with the previous and will tell you where trace
has diverged.

This method is pretty valuable when you try to find why certain areas
of the code have executed multiple times.


Properties
==========

Methods
=======

