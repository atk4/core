=========
Exception
=========

.. php:class:: Exception

Introduction
============

Exception provides several improvements over vanilla PHP exception class. The
most significant change is introduction of parameters.

.. php:attr:: params

Parameters will store supplementary information that can help identify and
resolve the problem. There are two ways to supply parameters, either during
the constructor or using addMoreInfo()

.. php:method:: __construct(error, code, previous)

    This uses same format as a regular PHP exception, but error parameter will
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
and previous exception as any normal PHP exception would, but to get the
parameters you would need to use:

.. php:method:: getParams()

    Return array that lists all parameters collected by exception.

Some param values may be objects.

.. php:method:: setMessage($message)

    Change message (subject) of a current exception. Primary use is for
    localization purposes.


Output Formatting
-----------------

Exception (at least for now) contains some code to make the exception actually
look good. This functionality may be removed in the later versions to
facilitate use of proper loggers. For now:


.. php:method:: getColorfulText()

Will return nice ANSI-colored exception that you can output to the console for
user to see. This will include the error, parameters and backtrace. The code
will also make an attempt to locate and highlight the code that have caused the
problem.

.. php:method:: getHTMLText()

Will return nice HTML-formatted exception that will rely on a presence of
Semantic UI. This will include the error, parameters and backtrace. The code
will also make an attempt to locate and highlight the code that have caused the
problem.

.. image:: exception-demo.png

Handling Exceptions in ATK Data and ATK UI
==========================================

Sometimes you want your exceptions to be displayed nicely. There are several ways:

Try and Catch block
-------------------


If you want, you can wrap your code inside try / catch block::

    try {
        // some code..
    } catch (\atk4\core\Exception $e) {
        // handle exception
    }

The other option is to use automatic exception catching, (:php:attr:`\atk4\ui\App::catch_exceptions`)
which will automatically catch any unhandled exception then pass it to :php:meth:`\atk4\ui\App::caughtException()`.

If you do not instantiate App, or set it up without automatic exception catching::

    $app = new \atk4\ui\App(['catch_exceptions' = false]);

then you might want to output message details yourself.

Use :php:meth:`Exception::getColorfulText` or :php:meth:`Exception::getHTMLText`::

    try {
        // some code..
    } catch (\atk4\core\Exception $e) {
        echo $e->getColorfulText();
    } catch (\Exception $e) {
        echo $e->getMessage();
    }

Finally if you don't want ANSII output, you can also do::

    echo strip_tags($e->getHTMLText());

Although it's not advisable to output anything else other than the Message to user (in production),
you can get values of additional parameters through::

    $e->getParams();
