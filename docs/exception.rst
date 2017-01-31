
=========
Exception
=========

.. php:class:: Exception

Introduction
============

Exception provides several improvements over vanilla PHP exception class. The 
most significant change is introduction of parameters.

.. php:attr:: params

Parameters will store supplimentary information that can help identify and
resolve the problem. There are two ways to supply params, either during
the constructor or using addMoreInfo()

.. php:method:: __construct(error, code, previous)

    This uses same format as a regular PHP exception, but error parametetr will
    now support array::
    
        throw new Exception(['Value is too big', 'max'=>$max]);

The other option is to supply error is:

.. php:method:: addMoreInfo(param, value)

    Augments exception by providing extra information. This is a typical use
    format::

        try {
            $field->validate();
        } catch (Validation_Exception $e) {
            $e->addMoreInfo('field', $field);
            throw $e;
        }

I must note that the reason for using parameters is so that the name of the
actual exception could be localized easily.

The final step is to actually get all the information from your exception.
Since the exception is backwards compatible, it will contain message, code
and previous exception as any normal PHP excetpion would, but to get the
parameters you would need to use:


.. php:method:: getParams()

    Return array that lists all params collected by exception.

Some param values may be objects.


Output Formatting
-----------------

Exception (at least for now) contains some code to make the exception actually
look good. This functionality may be removed in the later versions to
to facilitate use of proper loggers. For now:


.. php:method:: getColorfulText()

Will return nice ANSI-colored exception that you can output to the console
for user to see. This will include the error, params and backtrace. The
code will also make an attempt to locate and highlight the code that have
caused the problem.

.. php:method:: getColorfulText()

Will return nice HTML-formatted exception that will rely on a presence of
Semantic UI. This will include the error, params and backtrace. The
code will also make an attempt to locate and highlight the code that have
caused the problem.

.. image:: exception-demo.png

