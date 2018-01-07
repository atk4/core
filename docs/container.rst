==========
Containers
==========

There are two relevant traits in the Container mechanics. Your "container"
object should implement :php:trait:`ContainerTrait` and your child objects
should implement :php:trait:`TrackableTrait` (if not, the $owner/$elements
links will not be established)

If both parent and child implement :php:trait:`AppScopeTrait` then the property
of :php:attr:`AppScopeTrait::app` will be copied from parent to the child also.

If your child implements :php:trait:`InitializerTrait` then the method
:php:meth:`InitializerTrait::init` will also be invoked after linking is done.

You will be able to use :php:meth:`ContainerTrait::getElement()` to access
elements inside container::

    $object->add(new AnoterObject(), 'test');
    $another_object = $object->getElement('test');

If you additionally use :php:trait:`TrackableTrait` then your objects
also receive unique "name". From example above:

* $object->name == "app_object_4"
* $another_object->name == "app_object_4_test"



Name Trait
============

.. php:trait:: ObjectTrait

    Name trait only adds the 'name' property. Normally you don't have to use
    it because :php:trait:`TrackableTrait` automatically inherits this trait.
    Due to issues with PHP5 if both :php:trait:`ContainerTrait` and
    :php:trait:`TrackableTrait` are using :php:trait:`NameTrait` and then
    both applied on the object, the clash results in "strict warning".
    To avoid this, apply :php:trait:`NameTrait` on Containers only if you are
    NOT using :php:trait:`TrackableTrait`.

Properties
----------

.. php:attr:: name

    Name of the object.

Methods
-------

    None



Container Trait
===============

.. php:trait:: ContainerTrait

    If you want your framework to keep track of relationships between objects
    by implementing containers, you can use :php:trait:`ContainerTrait`.
    Example::

        class MyContainer extends OtherClass {
            use atk4\core\ContainerTrait;

            function add($obq, $args = []) {
                return $this->_add_Container($obj, $args);
            }
        }

        class MyItem  {
            use atk4\core\TrackableTrait;
        }

        Now the instances of MyItem can be added to instances of MyContainer
        and can keep track::

        $parent = new MyContainer();
        $parent->name = 'foo';
        $parent->add(new MyItem(), 'child1');
        $parent->add(new MyItem());

        echo $parent->getElement('child1')->name;
        // foo_child1

        if ($parent->hasElement('child1')) {
            $parent->removeElement('child1');
        }

        $parent->each(function($child) {
            $child->doSomething();
        });

    Child object names will be derived from the parent name.

Properties
----------

.. php:attr:: elements

    Contains a list of objects that have been "added" into the current
    container. The key is a "shot_name" of the child. The actual link to
    the element will be only present if child uses trait "TrackableTrait",
    otherwise the value of array key will be "true".

Methods
-------

.. php:method:: add($obj, $args = [])

    If you are using ContainerTrait only, then you can safely use this add()
    method. If you are also using factory, or initializer then redefine add()
    and call _add_Container, _add_Factory,.

.. php:method:: _addContainer($element, $args)

    Add element into container. Normally you should create a method
    add() inside your class that will execute this method. Because
    multiple traits will want to contribute to your add() method,
    you should see sample implementation in :php:class:`Object::add`.

    Your minimum code should be::

        function add($obj, $args = [])
        {
            return $this->_add_Container($obj, $args);
        }

    $args be in few forms::

        $args = ['child_name'];
        $args = 'child_name';
        $args = ['child_name', 'db'=>$mydb];
        $args = ['name'=>'child_name'];  // obsolete, backward-compatible

    Method will return the object. Will throw exception if child with same
    name already exist.

.. php:method:: removeElement($short_name)

    Will remove element from $elements. You can pass either short_name
    or the object itself. This will be called if :php:meth:`TrackableTrait::destroy`
    is called.

.. php:method:: _shorten($desired)

    Given the desired $name, this method will attempt to shorten the length
    of your children. The reason for shortening a name is to impose reasonable
    limits on overly long names. Name can be used as key in the GET argument
    or form field, so for a longer names they will be shortened.

    This method will only be used if current object has :php:trait:`AppScope`,
    since the application is responsible for keeping shortenings.

.. php:method:: getElement($short_name)

    Given a short-name of the element, will return the object. Throws exception
    if object with such short_name does not exist.

.. php:method:: hasElement($short_name)

    Given a short-name of the element, will return the object. If object with
    such short_name does not exist, will return false instead.

.. php:method:: _unique_element

    Internal method to create unique name for an element.



Trackable Trait
===============

.. php:trait:: TrackableTrait

    Trackable trait implements a few fields for the object that will maintain
    it's relationship with the owner (parent).

    When name is set for container, then all children will derive their names
    of the parent.

    * Parent: foo
    * Child:  foo_child1

    The name will be unique within this container.

Properties
----------

.. php:attr:: owner

    Will point to object which has add()ed this object. If multiple objects
    have added this object, then this will point to the most recent one.

.. php:attr:: short_name

    When you add item into the owner, the "short_name" will contain short name
    of this item.

Methods
-------

.. php:method:: getDesiredName

    Normally object will try to be named after it's class, if the name is omitted.
    You can override this method to implement a different mechanics.

    If you pass 'desired_name'=>'heh' to a constructor, then it will affect the
    preferred name returned by this method. Unlike 'name'=>'heh' it won't fail
    if another element with this name exists, but will add '_2' postfix.

.. php:method:: destroy

    If object owner is set, then this will remove object from it's owner elements
    reducing number of links to the object. Normally PHP's garbage collector
    should remove object as soon as number of links is zero.
