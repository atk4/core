==============================
Dependency Injection Container
==============================

.. php:trait:: DIContainerTrait

Agile Core implements basic support for Dependency Injection Container.

What is Dependency Injection
----------------------------

As it turns out many PHP projects have built objects which hard-code
dependencies on another object/class. For instance::

    $book = new Book();
    $book->name = 'foo';
    $book->save();         // saves somewhere??

The above code uses some ORM notation and the book record is saved into the
database. But how does Book object know about the database? Some frameworks
thought it could be a good idea to use GLOBALS or STATIC. PHP Community is
fighting against those patterns by using Dependency Injection which is a pretty
hot topic in the community.

In Agile Toolkit this has never been a problem, because all of our objects are
designed without hard dependencies, globals or statics in the first place.

"Dependency Injection" is just a fancy word for ability to specify other objects
into class constructor / property::

    $book = new Book($mydb);
    $book['name'] = 'foo';
    $book->save(); // saves to $mydb

What is Dependency Injection Container
--------------------------------------

By design your objects should depend on as little other objects as possible.
This improves testability of objects, for instance. Typically constructor can
be good for 1 or 2 arguments.

However in Agile UI there are components that are designed specifically to
encapsulate many various objects. CRUD for example is a fully-functioning
editing solution, but suppose you want to use custom form object::

    $crud = new CRUD([
        'formEdit' => new MyForm(),
        'formAdd'  => new MyForm()
    ]);

In this scenario you can't pass all of the properties to the constructor, and
it's easier to pass it through array of key/values. This pattern is called
Dependency Injection Container. Theory states that developers who use IDEs
extensively would prefer to pass "object" and not "array", however we typically
offer a better option::

    $crud = new CRUD();
    $crud->formEdit = new MyForm();
    $crud->formAdd  = new MyForm();

How to use DIContainerTrait
---------------------------

.. php:trait: DIContainerTrait

.. php:method: setDefaults($array, $strict = false)

.. php:method: setMissingProperty($array, $strict = false)

Calling this method will set object's properties. If any specified property
is undefined then it will be skipped. Here is how you should use trait::

    class MyObj {
        use DIContainerTrait;

        function __construct($defaults = []) {
            $this->setDefaults($defaults, true);
        }
    }

You can also extend and define what should be done if non-property is passed.
For example Button component allows you to pass value of $content and $class
like this::

    $button = new Button(['My Button Label', 'red']);

This is done by overriding setMissingProperty method::

    class MyObj {
        use DIContainerTrait {
            setMissingProperty as _setMissingProperty;
        }

        function __construct($defaults = []) {
            $this->setDefaults($defaults, true);
        }

        function setMissingProperty($key, $value, $strict = false) {
            // do something with $key / $value

            // will either cause exception or will ignorance
            $this->_setMissingProperty($key, $value, $strict);
        }
    }

