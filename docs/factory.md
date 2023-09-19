:::{php:namespace} Atk4\Core
:::

# Factory Class

:::{php:class} Factory
:::

## Introduction

This trait is used to initialize object of the appropriate class, handling
things like:

- determining name of the class with ability to override
- passing argument to constructors
- setting default property values

Thanks to Factory trait, the following code:

```
$button = $app->add(['Button', 'A Label', 'icon' => 'book', 'action' => My\Action::class]);
```

can replace this:

```
$button = new \Atk4\Ui\Button('A Label');
$button->icon = new \Atk4\Ui\Icon('book');
$button->action = new My\Action();
$app->add($button);
```

### Type Hinting

Agile Toolkit 2.1 introduces support for a new syntax. It is functionally
identical to a short-hand code, but your IDE will properly set type for
a `$button` to be `class Button` instead of `class View`:

```
$button = Button::addTo($view, ['A Label', 'icon' => 'book', 'action' => My\Action::class]);
```

The traditional `$view->add` will remain available, there are no plans to
remove that syntax.

## Class Name Resolution

An absolute/full class name must be always provided. Relative class name resolution was obsoleted/removed.

## Seed

Using "class" as opposed to initialized object yields many performance gains,
as initialization of the class may be delayed until it's required. For instance:

```
$model->hasMany('Invoices', Invoice::class);

// is faster than

$model->hasMany('Invoices', new Invoice());
```

That is due to the fact that creating instance of "Invoice" class is not required
until you actually traverse into it using `$model->ref('Invoices')` and can offer
up to 20% performance increase. But in some cases, you want to pass some information
into the object.

Suppose you want to add a button with an icon:

```
$button = $view->add('Button');
$button->icon = new Icon('book');
```

It's possible that some call-back execution will come before button rendering, so
it's better to replace icon with the class:

```
$button = $view->add('Button');
$button->icon = Icon::class;
```

In this case, however - it is no longer possible to pass the "book" parameter to
the constructor of the Icon class.

This problem is solved in ATK with "Seeds".

A Seed is an array consisting of class name/object, named and numeric arguments:

```
$seed = [Button::class, 'My Label', 'icon' => 'book'];
```

### Seed with and without class

There are two types of seeds - with class name and without. The one above contains
the class and is used when user needs a flexibility to specify a class:

```
$app->add(['Button', 'My Label', 'icon' => 'book']);
```

The other seed type is class-less and can be used in situations where there are no
ambiguity about which class is used:

```
$button->icon = ['book'];
```

Either of those seeds can be replaced with the Object:

```
$button = $app->add(new Button('My Label'));
$button->icon = new Icon('book');
```

If seed is a string then it would be treated as class name. For a class-less seed
it would be treaded as a first argument to the constructor:

```
$button = $app->add('Button');
$button->icon = 'book';
```

### Lifecycle of argument-bound seed

ATK only uses setters/getters when they make sense. Argument like "icon" is a very
good example where getter is needed. Here is a typical lifecycle of an argument:

1. when object is created "icon" is set to null
2. seed may have a value for "icon" and can set it to string, array or object
3. user may explicitly set "icon" to string, array or object
4. some code may wish to interact with icon and will expect it to be object
5. recursiveRender() will expect icon to be also added inside $button's template

So here are some rules for ATK and add-ons:

- use class-less seeds where possible, but indicate so in the comments
- keep seed in its original form as long as possible
- use getter (getIcon()) which would convert seed into object (if needed)
- add icon object into render-tree inside recursiveRender() method

If you need some validation (e.g. icon and iconRight cannot be set at the same time
by the button), do that inside recursiveRender() method or in a custom setter.

If you do resort to custom setters, make sure they return $this for better chaining.

Always try to keep things simple for others and also for yourself.

## Factory

As mentioned juts above - at some point your "Seed" must be turned into Object. This
is done by executing factory method.

:::{php:method} factory($seed, $defaults = [])
:::

Creates and returns new object. If is_object($seed), then it will be returned and
$defaults will only be sed if object implement DiContainerTrait.

In a conventional PHP, you can create and configure object before passing
it onto another object. This action is called "dependency injecting".
Consider this example:

```
$button = new Button('A Label');
$button->icon = new Icon('book');
$button->action = new Action(..);
```

Because Components can have many optional components, then setting them
one-by-one is often inconvenient. Also may require to do it recursively,
e.g. `Action` may have to be configured individually.

Agile Core implements a mechanism to make that possible through using Factory::factory()
method and specifying a seed argument:

```
use Atk4\Ui\Button;

$button = Factory::factory([Button::Class, 'A Label', 'icon' => ['book'], 'action' => new Action(..)]);
```

Note that passing 'icon' => ['book'] will also use factory to initialize icon object.

Finally, if you are using IDE and type hinting, a preferred code would be:

```
use Atk4\Ui\Button;

$button = new Button('A Label');
Factory::factory($button, ['icon' => ['book'], 'action' => new Action(..)]);
```

This will properly set type to $button variable, while still setting properties for icon/action. More
commonly, however, you would use this through the add() method:

```
use Atk4\Ui\Button;

$button = new Button('A Label');
$view->add([$button, 'icon' => ['book'], 'action' => new Action('..')]);
```

### Seed Components

Class definition - passed as the `$seed[0]` and is the only mandatory
component, e.g:

```
$button = Factory::factory([Button::class]);
```

Any other numeric arguments will be passed as constructor arguments:

```
$button = Factory::factory([Button::class, 'My Label', 'red', 'big']);

// results in

new Button('My Label', 'red', 'big');
```

Finally any named values inside seed array will be assigned to class properties
by using {php:meth}`DiContainerTrait::setDefaults`.

Factory uses `array_shift` to separate class definition from other components.

### Class-less seeds

You cannot create object from a class-less seed, simply because factory would not know which class
to use. However it can be passed as a second argument to the factory:

```
$this->icon = Factory::factory([Icon::class, 'book'], $this->icon);
```

This will use class icon and first argument 'book' as default, but would use existing seed version if
it was specified. Also it will preserve the object value of an icon.

### Factory Defaults

Defaults array takes place of $seed if $seed is missing components. $defaults is
using identical format to seed, but without the class. If defaults is not an
array, then it's wrapped into [].

Array that lacks class is called defaults, e.g.:

```
$defaults = ['Label', 'My Label', 'big red', 'icon' => 'book'];
```

You can pass defaults as second argument to {php:meth}`Factory::factory()`:

```
$button = Factory::factory([Button::class], $defaults);
```

Executing code above will result in 'Button' class being used with 'My Label' as
a caption and 'big red' class and 'book' icon.

You may also use `null` to skip an argument, for instance in the above example
if you wish to change the label, but keep the class, use this:

```
$label = Factory::factory([null, 'Other Label'], $defaults);
```

Finally, if you pass key/value pair inside seed with a value of `null` then
default value will still be used:

```
$label = Factory::factory(['icon' => null], $defaults);
```

This will result icon=book. If you wish to disable icon, you should use `false`
value:

```
$label = Factory::factory(['icon' => false], $defaults);
```

With this it's handy to pass icon as an argument and don't worry if the null is
used.

### Precedence and Usage

When both seed and defaults are used, then values inside "seed" will have
precedence:

- for named arguments any value specified in "seed" will fully override
  identical value from "defaults", unless if the seed's value is "null".
- for constructor arguments, the non-null values specified in "seed" will
  replace corresponding value from $defaults.

The next example will help you understand the precedence of different argument
values. See my description below the example:

```
class RedButton extends Button
{
    protected $icon = 'book';

    protected function init(): void
    {
        parent::init();

        $this->icon = 'right arrow';
    }
}

$button = Factory::factory([RedButton::class, 'icon' => 'cake'], ['icon' => 'thumbs up']);
// question: what would be $button->icon value here?
```

Factory will start by merging the parameters and will discover that icon is
specified in the seed and is also mentioned in the second argument - $defaults.
The seed takes precedence, so icon='cake'.

Factory will then create instance of RedButton with a default icon 'book'.
It will then execute {php:meth}`DiContainerTrait::setDefaults` with the
`['icon' => 'cake']` which will change value of $icon to `cake`.

The `cake` will be the final value of the example above. Even though `init()`
method is set to change the value of icon, the `init()` method is only executed
when object becomes part of RenderTree, but that's not happening here.

## Seed Merging

:::{php:method} mergeSeeds($seed, $seed2, ...)
:::

Two (or more) seeds can be merged resulting in a new seed with some combined
properties:

1. Class of a first seed will be selected. If specified as "null" will be picked
   from next seed.
2. If string as passed as any of the argument it's considered to be a class
3. If object is passed as any of the argument, it will be used instead ignoring
   all classes and numeric arguments.
   All the key->value pairs will be merged and passed into setDefaults().

Some examples:

```
Factory::mergeSeeds(['Button', 'Button Label'], ['Message', 'Message label']);
// results in ['Button', 'Button Label']

Factory::mergeSeeds([null, 'Button Label'], ['Message', 'Message Label']);
// results in ['Message', 'Button Label']);

Factory::mergeSeeds(['null, 'Label1', 'icon' => 'book'], ['icon' => 'coin', 'Button'], ['class' => ['red']]);
// results in ['Button', 'Label1', 'icon' => 'book', 'class' => ['red']]
```

Seed merging can also be used to merge defaults:

```
Factory::mergeSeeds(['label 1'], ['icon' => 'book']);
// results in ['label 1', 'icon' => 'book']
```

When object is passed, it will take precedence and absorb all named arguments:

```
Factory::mergeSeeds(
    ['null, 'Label1', 'icon' => 'book'],
    ['icon' => 'coin', 'Button'],
    new Message('foobar'),
    ['class' => ['red']]
);
// result is
// $obj = new Message('foobar');
// $obj->setDefaults(['icon' => 'book', 'class' => ['red']);
```

If multiple objects are specified then early ones take precedence while still
absorbing all named arguments.

### Default and Seed objects

When object is passed as 2nd argument to Factory::factory() it takes precedence over
all array-based seeds. If 1st argument of Factory::factory() is also object, then 1st
argument object is used:

```
Factory::factory([Icon::class, 'book'], ['pencil']);
// book

Factory::factory([Icon::class, 'book'], new Icon('pencil'));
// pencil

Factory::factory(new Icon('book'), new Icon('pencil'));
// book
```

## Usage in frameworks

There are several ways to use Seed Merging and Agile UI / Agile Data makes use
of those patterns when possible.

### Specify Icon for a Button

As you may know, Button class has icon property, which may be specified as a
string, seed or object:

```
$button = $app->add(['Button', 'icon' => 'book']);
```

Well, to implement the button internally, render method uses this:

```
// in Form
$this->buttonSave = Factory::factory([Button::class], $this->buttonSave);
```

So the value you specify for the icon will be passed as:

- string: argument to constructor of `Button()`.
- array: arguments for constructors and inject properties
- object: will override return value

### Specify Layout

The first thing beginners learn about Agile Toolkit is how to specify layout:

```
$app = new \Atk4\Ui\App('Hello World');
$app->initLayout('Centered');
```

The argument for initLayout is passed to factory:

```
$this->layout = Factory::factory($layout);
```

The value you specify will be treated like this:

- string: specify a class (prefixed by Layout)
- array: specify a class and allow to pass additional argument or constructor options
- object: will override layout

### Form::addField and Table::addColumn

Agile UI is using form field classes from namespace \Atk4\Ui\FormField.
A default class is 'Line' but there are several ways how it can be overridden:

- User can specify $ui['form'] / $ui['table'] property for model's field
- User can pass 2nd parameter to addField()
- Class can be inferred from field type

Each of the above can specify class name, so with 3 seed sources they need
merging:

```
$seed = Factory::mergeSeeds($decorator, $field->ui, $inferred, [\Atk4\Ui\FormField\Line::class, 'form' => $this]);
$decorator = Factory::factory($seed, null, 'FormField');
```

Passing an actual object anywhere will use it instead even if you specify seed.

Specify Form Field

### addField, addButton, etc

Model::addField, Form::addButton, FormLayout::addHeader imply that the class of
an added object is known so the argument you specify to those methods ends up
being a factory's $default:

```
public function addButton($label)
{
    return $this->add(
        Factory::factory([Button::class, null, 'secondary'], $label);
        'Buttons'
    );
}
```

in this code factory will use a seed with a `null` for label, which means, that
label will be actually taken from a second argument. This pattern enables 3
ways to use addButton():

```
$form->addButton('click me');
// adds a regular button with specified label, as expected

$form->addButton(['click me', 'red', 'icon' => 'book']);
// specify class of a button and also icon

$form->addButton(new MyButton('click me'));
// use an object specified instead of a button
```

A same logic can be applied to addField:

```
$model->addField('is_vip', ['type' => 'boolean']);
// class = Field, type = boolean

$model->addField('is_vip', ['boolean'])
// new Field('boolean'), same result

$model->addField('is_vip', new MyBoolean());
// new MyBoolean()
```

and the implementation uses factory's default:

```
$field = Factory::factory($this->fieldSeed);
```

Normally the field class property is a string, which will be used, but it can
also be array.
