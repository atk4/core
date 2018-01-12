=================
Initializer Trait
=================

.. php:trait:: InitializerTrait

Introduction
============

With our traits objects now become linked with the "owner" and the "app".
Initializer trait allows you to define a method that would be called after
object is linked up into the environment.

Declare a object class in your framework::

    class FormField {
        use AppScopeTrait;
        use TrackableTrait;
        use InitializerTrait;

    }

    class FormField_Input extends FormField {

        public $value = null;

        function init() {
            parent::init();

            if($_POST[$this->name) {
                $this->value = $_POST[$this->name];
            }
        }

        function render() {
            return '<input name="'.$this->name.'" value="'.$value.'"/>';
        }
    }

Properties
==========

.. php:attr:: _initialized

    Internal property to make sure you have called parent::init() properly.

Methods
=======

.. php:method:: init()

    A blank init method that should be called. This will detect the problems
    when init() methods of some of your base classes has not been executed and
    prevents from some serious mistakes.

If you wish to use traits class and extend it, you can use this in your base
class::

    class FormField {
        use AppScopeTrait;
        use TrackableTrait;
        use InitializerTrait {
            init as _init
        }

        public $value = null;

        function init() {
            $this->_init();   // call init of InitializerTrait

            if($_POST[$this->name) {
                $this->value = $_POST[$this->name];
            }
        }
    }
