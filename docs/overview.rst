========
Overview
========

Agile Core is a collection of PHP Traits for designing object-oriented frameworks


Run-Time Tree (containers)
==========================

When you want your framework to look after relationships between objects by
implementing containers, you can use :php:trait:`ContainerTrait`.

You will be able to use :php:meth:`ContainerTrait::getElement()` to access
elements inside container::

    $object->add(new AnoterObject(), 'test');
    $another_object = $object->getElement('test');

If you additionally use :php:trait:`TrackableTrait` then your objects
also receive unique "name". From example above:

* $object->name == "app_object_4"
* $another_object->name == "app_object_4_test"

Initializers
============

When object is created, the constructor is executed. Sometimes your object
needs to be aware of it's environment, and that's why initializer will
allow your developers to extend init() method, that will be called after
your new object is integrated into the environment, if you use
:php:trait:`InitializerTrait`::

    class MyClass {
        use InitializerTrait;

        function init()
        {
            parent::init();

            // some variables are available here
            // echo $this->owner;
            // echo $this->name;
            // echo $this->app;
        }
    }


Factory
=======

Normally you can only add exsiting objects into your run-time tree. Factory
trait will allow you to specify the class name::

    $object->add('OtherObject');

This will also enable similar features for Modelable objects::

    $object->setModel('MyModel');
    // same as
    $object->setModel(new Model_MyModel);

You can specify namespaces with backslash or regular slash::

    $object->setModel('data/MyModel');
    // same as
    $object->setModel(new data\Model_MyModel);


Dynamic Methods
===============

Adds ability to add methods into objects dynamically. That's like a "trait"
feature of a PHP, but implemented in run-time::

    $object->addMethod('test', function($o, $args){ echo 'hello, '.$args[0]; } 
    $object->test('world');

There are also methods for removing and checking if methods exists, so::

    method_exists($object, 'test');
    // should use now
    $object->hasMethod('test');


Hooks
=====

Adds and trigger hooks for objects::

    $object->addHook('test', function($o){ echo 'hello'; }
    $object->addHook('test', function($o){ echo 'world'; }

    $object->hook('test');


Modelable Objects
=================

In an MVP concept you have 3 types of objects - Models, Views and Presenter.
The Presenter is responsible for creating and linking View and Model together.

Views are generic presentation widgets that can gain some insight into your
data through the Model declaration.


Modelable trait allows you to associate object with a Model::

    $form->setModel('Order');

    // or 

    $grid->setModel($order->ref('Items'), ['name', 'qty', 'price']);

Quick Exception
===============

When you are throwing exceptinon somewhere in your logic, you have to collect
enough information about the context. Sometimes it's easier to let your
framework do it for you::

    throw $object->exception(['Incorrect foo value', 'foo'=>$bar]);

This is similar to the regular exception, however in addition to back-trace
this will capture information about $object. This object will also be
able to add more information into your query::

    throw $db->exception('Bad Query', 'QueryException');

    class QueryException extends Exception {
        protected $query;

        function __construct($object){
            $this->query = $object->getDebugQuery();
        }
    }

App Scope
=========

Typical software design will create the application scope. Most frameworks
relies on "static" properties, methods and classes. This does puts some
limitations on your implementation (you can't have multiple applications).

App Scope will pass the 'app' property into all the object that you're
adding, so that you know for sure which application you work with::

    $object1->add('Object2');

    class Object2 {
        use AppScopeTrait;
        use InitializerTrait;

        function init() {

            parent::init();

            echo 'app is = '.$this->app;
        }
    }

Session
=======

When application is executed in environment, some objects of the applications
may want to "record their state" in session scope. Technically this could
be routed through the data source in the application that handles the session
but PHP has a wonderful support for $_SESSION already.

Session trait makes it possible for objects to have unique data-store
inside a session. 

This feature would me used by Views / Widgets that needs session info.

Syntax::

    $this->setField('search', $this->recall('search', null));

    // on submit

    $this->memorize('search', $_POST['search']);

The session store is unique for each object identified by their "name"
property.

DebugTrait
==========

This allows your objects to execute::

    $object->debug();
    $object->log('something happened');
    $object->warn('bad things happen');

The debug will only be collected if the debug mode is turned on, otherwise
calls to log() and warn() will be ignored.

