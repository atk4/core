=============
Factory Trait
=============

.. php:trait:: FactoryTrait

Introduction
============

This trait can be used to dynamically create new objects by their class
names. It also contains method for class name normalization.

.. php:method:: factory($seed, $defaults = [])

Creates and returns new object. If object is passed as $seed parameter,
then same object is returned, but you can change some properties of this object by using
$defaults array.

Seed
====

In a conventional PHP, you can create and configure object before passing
it onto another object. This action is called "dependency injecting".
Consider this example::

    $button = new Button('Label');
    $button->icon = new Icon('book');
    $button->action = new Action(..);

Because Components can have many optional components, then setting them
one-by-one is often inconvenient. Also may require to do it recursively,
e.g. ``Action`` may have to be configured individually.

On top of that, there are also namespaces to consider and quite often you would want to use
\3rdparty\bootstrap\Button() instead of default button.

Agile Core implements a mechanism to make that possible through using factory() method and
specifying a seed argument::

    $button = $this->factory(['Button', 'Label', 'icon'=>['book'], 'action'=>new Action(..)]);

it has the same effect, but is shorter. Note that passing 'icon'=>['book'] will also
use factory to initialize icon object.

Seed Components
---------------

Class definition - passed as the ``$seed[0]`` and is the only mandatory component, e.g::

    $button = $this->factory(['Button']);

Any other numeric arguments will be passed as constructor arguments::

    $button = $this->factory(['Button', 'My Label', 'red', 'big']);

    // results in

    new Button('My Label', 'red', 'big');

Finally any named values inside seed array will be assigned to class properties by using
:php:meth:`DIContainerTrait::setDefaults`.

Factory uses `array_shift` to separate class definition from other components.

Factory Defaults
----------------

Array that lacks class is called defaults, e.g.::

    $defaults = ['My Label', 'red', 'big', 'icon'=>'book'];

You can pass defaults as second argument to :php:meth:`FactoryTrait::factory()`::

    $button = $this->factory(['Button'], $defaults);

Defaults is quite safe and will be often used for example in ``Model->addField($name, $defaults)``

Precedence
----------

When both seed and defaults are used, then values inside "seed" will have precedence:

 - for named arguments any value specified in "seed" will fully override identical value from "defaults"
 - for constructor arguments, the values specified in "seed" will be passed first, and "defaults" will go after.

For arguments that are arrays (e.g. $view->class) value in seed will override value in "defaults" and then will
be merged with existing property value::

    class RedButton extends Button {
        public $class = ['red'];
    }

    $button = $this->factory(['RedButton', 'class'=>['big']], ['class'=>['small']);

    // $button->class == ['red', 'big']

Namespace
=========

You might have noticed, that seeds do not specify namespace. This is because factory relies on $app
to normalize your class name.

.. php:method:: normalizeClassName($name, $prefix = null)

Seed can use '/my/namespace/Class' where '/' are used instead of '\' to separate
namespaces. This is because "\" when used within string have special properties
and also to indicate that some modications may be applied.

Normalize will never change string that contains '\' and will ignore any prefix
instructons::

    $button = $this->factory(['My\\Namespace\\RedButton'], null, 'other/prefix');

A regular slashes, may be used in various combinations. Here are few things
to consider:

    - 3rd argument of factory() may specify a contextual prefix.
    - Application may specify a global default prefix
    - user may want to specify extra namespace to a class
    - user may want to fully specify namespace

.. _contextual_prefix:

Contextual Prefix
-----------------

Methods such as `$form->addField()` or `$app->initLayout()` often use prefixing::

    function initLayout($layout) {
        $this->layout = $this->factory($layout, ['app'=>$this], 'Layout');
    }

The above method can then be used with string argument, array or even object and
will still work consistently. If you specify 'Centered' layout, then it will
be prefixed with 'Layout\Centered'.

This is called Contextual Prefix and is used in various methods throughout
Agile Toolkit:

 - Form::addField('age', ['Hidden']); // uses FormField\Hidden class
 - Table::addColumn('status', ['Checkbox']); // uses TableColumn\Checkbox class
 - App::initLayout('Admin'); // uses Layout\Admin class

Global Prefix
-------------

Application class may specify how to add a global namespace. For example,
\atk4\ui\App will use prefix class name with "\atk4\ui\", unless, of course,
you override that somehow.

This is done, so that add-ons may intercept generation of Factory class and
have control over the code like this::

    $button = $this->add(['Button']);

By substituting \atk4\ui\Button with a different button implementation. It's
even possible to verify if class exists before prefixing or use routing maps,
but that's up to the ``$this->app->normalizeClassNameApp()``

How to properly use
-------------------

If you are building some new component that may have plug-ins inside a specific
namespace, use this::

    function addPlugin($name) {
        $plugin = $this->factory($name, ['my_comp'=>$this], '/my/namespace/plugin');
    }


Use with add()
==============

:php:meth:`ContainerTrait::add()` will allow first argument to be Seed but only
if the object also uses FactoryTrait. This is exactly the case for Agile UI / View
objects, so you can supply seed to add::

    $view->add(['Button', 'class'=>['red']]);

Method add() however only takes one argument and you cannot specify defaults or
prefix.

In most scenarios, you don't have to use factory() directly, simply use add()

Properties
==========

None

Methods
=======


