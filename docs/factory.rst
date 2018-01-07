=============
Factory Trait
=============

.. php:trait:: FactoryTrait

Introduction
============

This trait can be used to dynamically create new objects by their class
names. It also contains method for class name normalization.

.. php:method:: factory($seed, $defaults = [])

Creates and returns new object. If object is passed as $seed parameter, then
same object is returned, but you can change some properties of this object by
using $defaults array.

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

On top of that, there are also namespaces to consider and quite often you would
want to use \3rdparty\bootstrap\Button() instead of default button.

Agile Core implements a mechanism to make that possible through using factory()
method and specifying a seed argument::

    $button = $this->factory(['Button', 'Label', 'icon'=>['book'], 'action'=>new Action(..)]);

it has the same effect, but is shorter. Note that passing 'icon'=>['book'] will
also use factory to initialize icon object.

Seed Components
---------------

Class definition - passed as the ``$seed[0]`` and is the only mandatory
component, e.g::

    $button = $this->factory(['Button']);

Any other numeric arguments will be passed as constructor arguments::

    $button = $this->factory(['Button', 'My Label', 'red', 'big']);

    // results in

    new Button('My Label', 'red', 'big');

Finally any named values inside seed array will be assigned to class properties
by using :php:meth:`DIContainerTrait::setDefaults`.

Factory uses `array_shift` to separate class definition from other components.

Seed Merging
------------

.. php:method:: mergeSeeds($seed, $seed2, ...)

Two (or more) seeds can be merged resulting in a new seed with some combined
properties:

1. Class of a first seed will be selected. If specified as "null" will be picked
    from next seed.
2. If string as passed as any of the argument it's considered to be a class
3. If object is passed as any of the argument, it will be used instead ignoring
   all classes and numeric arguments.
   All the key->value pairs will be merged and passed into setDefaults().

Some examples::

    mergeSeeds(['Button', 'Button Label'], ['Message', 'Message label']);
    // results in ['Button', 'Button Label']

    mergeSeeds([null, 'Button Label'], ['Message', 'Message Label']);
    // Results in ['Message', 'Button Label']);

    mergeSeeds(['null, 'Label1', 'icon'=>'book'], ['icon'=>'coin', 'Button'], ['class'=>['red']]);
    // Results in ['Button', 'Label1', 'icon'=>'book', 'class'=>['red']]

Seed merging can also be used to merge defaults::

    mergeSeeds(['label 1'], ['icon'=>'book']);
    // results in ['label 1', 'icon'=>'book']

When object is passed, it will take precedence and absorb all named arguments::

    mergeSeeds(
        ['null, 'Label1', 'icon'=>'book'],
        ['icon'=>'coin', 'Button'],
        new Message('foobar'),
        ['class'=>['red']]
    );
    // result is
    // $obj = new Message('foobar');
    // $obj->setDefaults(['icon'=>'book', 'class'=>['red']);

If multiple objects are specified then early ones take precedence while still
absorbing all named arguments.

Default and Seed objects
------------------------

When object is passed as 2nd argument to factory() it takes precedence over
all array-based seeds. If 1st argument of factory() is also object, then 1st
argument object is used::

    factory(['Icon', 'book'], ['pencil']);
    // book

    factory(['Icon', 'book'], new Icon('pencil')];
    // pencil

    factory(new Icon('book'), new Icon('pencil')];
    // book

Usage in frameworks
===================

There are several ways to use Seed Merging and Agile UI / Agile Data makes use
of those patterns when possible.

Specify Icon for a Button
-------------------------

As you may know, Button class has icon property, which may be specified as a
string, seed or object::

    $button = $app->add(['Button', 'icon'=>'book']);

Well, to implement the button internally, render method uses this::

    $this->button = $this->factory(['Button'], $this->button);

So the value you specify for the icon will be passed as:

 - string: argument to constructor of `Button()`.
 - array: arguments for constructors and inject properties
 - object: will override return value

Specify Layout
--------------

The first thing beginners learn about Agile Toolkit is how to specify layout::

    $app = new \atk4\ui\App('Hello World');
    $app->initLayout('Centered');

The argument for initLayout is passed to factory::

    $this->layout = $this->factory($layout, null, 'Layout');

The value you specify will be treated like this:

 - string: specify a class (prefixed by Layout\)
 - array: specify a class and allow to pass additional argument or constructor options
 - object: will override layout

Form::addField and Table::addColumn
-----------------------------------

Agile UI is using form field classes from namespace \atk4\ui\FormField\.
A default class is 'Line' but there are several ways how it can be overridden:

 - User can specify $ui['form'] / $ui['table'] property for model's field
 - User can pass 2nd parameter to addField()
 - Class can be inferred from field type

Each of the above can specify class name, so with 3 seed sources they need
merging::

    $seed = mergeSeeds($decorator, $field->ui, $inferred, ['Line', 'form' => $this]);
    $decorator = factory($seed, null, 'FormField');

Passing an actual object anywhere will use it instead even if you specify seed.

Specify Form Field

addField, addButton, etc
------------------------

Model::addField, Form::addButton, FormLayout::addHeader imply that the class of
an added object is known so the argument you specify to those methods ends up
being a factory's $default::

    function addButton($label) {
        return $this->add(
            $this->factory(['Button', null, 'secondary'], $label);
            'Buttons'
        );
    }

in this code factory will use a seed with a `null` for label, which means, that
label will be actually taken from a second argument. This pattern enables 3
ways to use addButton()::

    $form->addButton('click me');
    // Adds a regular button with specified label, as expected

    $form->addButton(['click me', 'red', 'icon'=>'book']);
    // Specify class of a button and also icon

    $form->addButton(new MyButton('click me'));
    // Use an object specified instead of a button

A same logic can be applied to addField::

    $model->addField('is_vip', ['type'=>'boolean']);
    // class = Field, type = boolean

    $model->addField('is_vip', ['boolean'])
    // new Field('boolean'), same result

    $model->addField('is_vip', new MyBoolean());
    // new MyBoolean()

and the implementation uses factory's default::

    $field = $this->factory($this->_field_class, $arg, '\atk4\data');

Normally the field class property is a string, which will be used, but it can
also be array.


Factory Defaults
================

Defaults array takes place of $seed if $seed is missing components. $defaults is
using identical format to seed, but without the class. If defaults is not an
array, then it's wrapped into [].

Array that lacks class is called defaults, e.g.::

    $defaults = ['Label', 'My Label', 'big red', 'icon'=>'book'];

You can pass defaults as second argument to :php:meth:`FactoryTrait::factory()`::

    $button = $this->factory(['Button'], $defaults);

Executing code above will result in 'Button' class being used with 'My Label' as
a caption and 'big red' class and 'book' icon.

You may also use ``null`` to skip an argument, for instance in the above example
if you wish to change the label, but keep the class, use this::

    $label = $this->factory([null, 'Other Label'], $defaults);

Finally, if you pass key/value pair inside seed with a value of ``null`` then
default value will still be used::

    $label = $this->factory(['icon'=>null], $defaults);

This will result icon=book. If you wish to disable icon, you should use ``false``
value::

    $label = $this->factory(['icon'=>false], $defaults);

With this it's handy to pass icon as an argument and don't worry if the null is
used.

Precedence and Usage
--------------------

When both seed and defaults are used, then values inside "seed" will have
precedence:

 - for named arguments any value specified in "seed" will fully override
   identical value from "defaults", unless if the seed's value is "null".
 - for constructor arguments, the non-null values specified in "seed" will
   replace corresponding value from $defaults.

The next example will help you understand the precedence of different argument
values. See my description below the example::

    class RedButton extends Button {
        protected $icon = 'book';

        function init() {
            parent::init();

            $this->icon = 'right arrow';
        }
    }

    $button = $this->factory(['RedButton', 'icon'=>'cake'], ['icon'=>'thumbs up']);
    // Question: what would be $button->icon value here?


Factory will start by merging the parameters and will discover that icon is
specified in the seed and is also mentioned in the second argument - $defaults.
The seed takes precedence, so icon='cake'.

Factory will then create instance of RedButton with a default icon 'book'.
It will then execute :php:meth:`DIContainerTrait::setDefaults` with the
`['icon'=>'cake']` which will change value of $icon to `cake`.

The `cake` will be the final value of the example above. Even though `init()`
method is set to change the value of icon, the `init()` method is only executed
when object becomes part of RenderTree, but that's not happening here.

Namespace
=========

You might have noticed, that seeds do not specify namespace. This is because
factory relies on $app to normalize your class name.

.. php:method:: normalizeClassName($name, $prefix = null)

Seed can use '/my/namespace/Class' where '/' are used instead of '\' to separate
namespaces. The '/' will be translated into '\' and they have exactly the same
meaning::

    $button = $this->factory(['\My\Namespace\RedButton'], null, 'other/prefix');

    // same as

    $button = $this->factory(['/My/Namespace/RedButton'], null, 'other/prefix');

A regular slashes, may be used in various combinations. Here are few things
to consider.

    - 3rd argument of factory() is called "Contextual Prefix" and is explained
      below.
    - Application may change namespace with "Global Prefixing"
    - User may want to specify extra namespace inside seed
    - User may want to override namespace entirely

Motivation
----------

I have created namespace prefixing as described here for the following reasons:

 - PHP has capability to create class names out of strings, unlike compiled
   languages that have type safety. I see that as a benefit and a feature of PHP
   so allowing namespace logic can lift some extra thinking from you.

 - Agile Toolkit is designed to be simple and powerful. The code which uses
   seeds is very easy to read and understand even for non-programmers.

 - Use of seeds have some great potential for extending. If someone is looking
   to integrate Agile UI with Drupal, they might need a specific functionality
   out of their 'Button' implementation. Use of seed allow integrator to
   substitute classes and redirect button class to a different namespace.
   Alternatively you would have to change "use" keywords making your code less
   portable.

Features
--------

Class normalization and prefixing offer several good features to the rest of the
framework:

 - Allow to work with App and without App.
 - Contextual prefixing is great for creating separate class namespaces:
   'Checkbox' -> 'FormField/Checkbox'
 - Namespace prefix "FormLayout" can be used for discovery of possible classes.
 - Global prefixing logic can be quite sophisticated and implemented inside App.
 - Use of forward slashes helps avoid errors

.. _contextual_prefix:

Seed class
----------

Here are some Seed usage examples. First the basic usage where a class specified
by you "Button" is converted into ``\atk4\ui\Button``::

    // \atk4\ui\Button
    $app->add(['Button', 'My Label']);

    // \atk4\ui\Button\WithDropdown - (non-existant class)
    $app->add(['Button\WithDropdown', 'My Label']);

    // \MyNamespace\Button
    $app->add(['\MyNamespace\Button', 'My Label']);


Contextual Prefix
-----------------

Methods such as `$form->addField()` or `$app->initLayout()` often use prefixing::

    function initLayout($layout) {
        $this->layout = $this->factory($layout, ['app'=>$this], 'Layout');
    }

The above method can then be used with string argument, array or even object and
will still work consistently. If you specify 'Centered' layout, then it will be
prefixed with 'Layout\Centered'.

This is called Contextual Prefix and is used in various methods throughout
Agile Toolkit:

 - Form::addField('age', ['Hidden']); // uses FormField\Hidden class
 - Table::addColumn('status', ['Checkbox']); // uses TableColumn\Checkbox class
 - App::initLayout('Admin'); // uses Layout\Admin class

Here are some examples of contextual prefixing::

    // \atk4\ui\FormField\Checkbox
    $form = $app->add('Form');
    $form->addField('agree_to_terms', 'Checkbox', 'I Agree to Terms of Service');

    // \MyNamespace\Checkbox
    $form = $app->add('Form');
    $form->addField('agree_to_terms', '\MyNamespace\Checkbox', 'I Agree to Terms of Service');

Specifying contextual prefix will still leave it up for global prefixing, but
if you want to fully specify a namespace, then you can use ``\Prefix``. If you
build your own component in your own namespace with some features, you can use
this technique::

    namespace my\auth;

    class AuthController {
        use FactoryTrait;    // implements $this->factory
        use AppScopeTrait;   // links $this->app
        use ContainerTrait;  // implements $this->add

        function enableFeature($feature) {
            return $this->add($this->factory($feature, ['myattr' => $this], '\my\auth\feature');
        }
    }

To use this AuthController you would write::

    $auth = $app->add('my\auth\AuthController');

    // \my\auth\feature\facebook
    $auth->enableFeature('facebook');

This contextual prefixing avoids global prefixing.

Global Prefix
-------------

Application class may specify how to add a global namespace. For example,
\atk4\ui\App will use prefix class name with "\atk4\ui\", unless, of course,
you override that somehow.

This is done, so that add-ons may intercept generation of Factory class and
have control over the code like this::

    $button = $this->add(['Button']);

By substituting \atk4\ui\Button with a different button implementation. It's
even possible to verify if class exists before prefixing or use routing maps.
Neither Agile Core nor Agile UI implement such logic but you can build your own
``$this->app->normalizeClassNameApp()``.

The next example will replace all the ``Grid`` classes with the one you have
implemented inside your own namespace::

    class MyApp extends \atk4\ui\App {
        function normalizeClassNameApp($class) {
            if ($class == 'Grid') {
                return '\myextensions\Grid';
            }

            return parent::normalizeClassNameApp($class);
        }
    }


Use with add()
==============

:php:meth:`ContainerTrait::add()` will allow first argument to be Seed, but
only if the object also uses FactoryTrait. This is exactly the case for
Agile UI / View objects, so you can supply seed to add::

    $grid = $app->add(['Grid', 'toolbar'=>false']);

Method add() however only takes one argument and you cannot specify defaults or
prefix.

In most scenarios, you don't have to use factory() directly, simply use add()
