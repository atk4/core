=============
Session Trait
=============

.. php:trait:: SessionTrait


.. warning:: SESSIONS WILL BE REMOVED FROM Agile core IN THE FUTURE.

Introduction
============

    not yet implemented

Properties
==========

.. php:attr:: session_key

    Internal property to make sure that all session data will be stored in one
    "container" (array key).

Methods
=======

.. php:method:: startSession($options = [])

    Create new session.

.. php:method:: destroySession()

    Destroy existing session.

.. php:method:: memorize($key, $value)

    Remember data in object-relevant session data.

.. php:method:: learn($key, $default = null)

    Similar to memorize, but if value for key exist, will return it.

.. php:method:: recall($key, $default = null)

    Returns session data for this object. If not previously set, then $default
    is returned.

.. php:method:: forget($key = null)

    Forget session data for arg $key. If $key is omitted will forget all
    associated session data.
