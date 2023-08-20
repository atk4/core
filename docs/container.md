:::{php:namespace} Atk4\Core
:::

# Containers

There are two relevant traits in the Container mechanics. Your "container"
object should implement {php:trait}`ContainerTrait` and your child objects
should implement {php:trait}`TrackableTrait` (if not, the $owner/$elements
links will not be established)

If both parent and child implement {php:trait}`AppScopeTrait` then the property
of {php:attr}`AppScopeTrait::$app` will be copied from parent to the child also.

If your child implements {php:trait}`InitializerTrait` then the method
{php:meth}`InitializerTrait::init` will also be invoked after linking is done.

You will be able to use {php:meth}`ContainerTrait::getElement()` to access
elements inside container:

```
$object->add(new AnotherObject(), 'test');
$anotherObject = $object->getElement('test');
```

If you additionally use {php:trait}`TrackableTrait` together with {php:trait}`NameTrait`
then your objects also receive unique "name". From example above:

- `$object->name` == "app_object_4"
- `$anotherObject->name` == "app_object_4_test"

## Name Trait

:::{php:trait} NameTrait
Name trait only adds the 'name' property. Normally you don't have to use
it because {php:trait}`TrackableTrait` automatically inherits this trait.
Due to issues with PHP5 if both {php:trait}`ContainerTrait` and
{php:trait}`TrackableTrait` are using {php:trait}`NameTrait` and then
both applied on the object, the clash results in "strict warning".
To avoid this, apply {php:trait}`NameTrait` on Containers only if you are
NOT using {php:trait}`TrackableTrait`.
:::

### Properties

:::{php:attr} name
Name of the object.
:::

### Methods

None

## CollectionTrait

:::{php:trait} CollectionTrait
This trait makes it possible for you to add child objects
into your object, but unlike "ContainerTrait" you can use
multiple collections stored as different array properties.

This class does not offer automatic naming, so if you try
to add another element with same name, it will result in
exception.
:::

Example:

```
class Form
{
    use Core\CollectionTrait;

    protected $fields = [];

    public function addField(string $name, $seed = [])
    {
        $seed = Factory::mergeSeeds($seed, [FieldMock::class]);

        $field = Factory::factory($seed, ['name' => $name]);

        return $this->_addIntoCollection($name, $field, 'fields');
    }

    public function hasField(string $name): bool
    {
        return $this->_hasInCollection($name, 'fields');
    }

    public function getField(string $name)
    {
        return $this->_getFromCollection($name, 'fields');
    }

    public function removeField(string $name)
    {
        $this->_removeFromCollection($name, 'fields');
    }
}
```

### Methods

:::{php:method} _addIntoCollection(string $name, object $object, string $collection)
Adds a new element into collection:

```
public function addField(string $name, $seed = [])
{
    $field = Factory::factory($seed);

    return $this->_addIntoCollection($name, $field, 'fields');
}
```

Factory usage is optional but would allow you to pass seed into addField()
:::

:::{php:method} _removeFromCollection(string $name, string $collection)
Remove element with a given name from collection.
:::

:::{php:method} _hasInCollection(string $name, string $collection)
Return object if it exits in collection and false otherwise
:::

:::{php:method} _getFromCollection(string $name, string $collection)
Same as _hasInCollection but throws exception if element is not found
:::

:::{php:method} _shortenMl($string $ownerName, string $itemShortName)
Implements name shortening
:::

Shortening is identical to {php:meth}`ContainerTrait::_shorten`.

Your object can this train together with ContainerTrait. As per June 2019
ATK maintainers agreed to gradually refactor ATK Data to use CollectionTrait
for fields, relations, actions.

## Container Trait

:::{php:trait} ContainerTrait
If you want your framework to keep track of relationships between objects
by implementing containers, you can use {php:trait}`ContainerTrait`.
Example:

```
class MyContainer extends OtherClass
{
    use Atk4\Core\ContainerTrait;

    public function add(object $obq, $args = []): object
    {
        $this->_addContainer($obj, is_string($args) ? ['name' => $args] : $args);

        return $obj;
    }
}

class MyItem
{
    use Atk4\Core\TrackableTrait;
    use Atk4\Core\NameTrait;
}
```

Now the instances of MyItem can be added to instances of MyContainer
and can keep track:

```
$parent = new MyContainer();
$parent->name = 'foo';
$parent->add(new MyItem(), 'child1');
$parent->add(new MyItem());

echo $parent->getElement('child1')->name;
// foo_child1

if ($parent->hasElement('child1')) {
    $parent->removeElement('child1');
}

foreach ($parent as $child) {
    $child->doSomething();
}
```

Child object names will be derived from the parent name.
:::

### Properties

:::{php:attr} elements
Contains a list of objects that have been "added" into the current
container. The key is a "shot_name" of the child. The actual link to
the element will be only present if child uses both {php:trait}`TrackableTrait`
and {php:trait}`NameTrait` traits, otherwise the value of array key will be "true".
:::

### Methods

:::{php:method} add($obj, $args = [])
If you are using ContainerTrait only, then you can safely use this add()
method. If you are also using factory, or initializer then redefine add()
and call _addContainer, _addFactory,.
:::

:::{php:method} _addContainer(object $element, array $args): void
Add element into container. Normally you should create a method
add() inside your class that will execute this method. Because
multiple traits will want to contribute to your add() method,
you should see sample implementation in {php:meth}`ContainerTrait::add`.

Your minimum code should be:

```
public function add(object $obj, $args = []): object
{
    $this->_addContainer($obj, is_string($args) ? ['name' => $args] : $args);

    return $obj;
}
```

$args be in few forms:

```
$args = ['child_name'];
$args = 'child_name';
$args = ['child_name', 'db' => $mydb];
$args = ['name' => 'child_name']; // obsolete, backward-compatible
```

Method will return the object. Will throw exception if child with same
name already exist.
:::

:::{php:method} removeElement($shortName)
Will remove element from $elements. You can pass either shortName
or the object itself. This will be called if {php:meth}`TrackableTrait::destroy`
is called.
:::

:::{php:method} _shorten($string $ownerName, string $itemShortName)
Given the long owner name and short child name, this method will attempt to shorten the length
of your children. The reason for shortening a name is to impose reasonable
limits on overly long names. Name can be used as key in the GET argument
or form field, so for a longer names they will be shortened.

This method will only be used if current object has {php:trait}`AppScopeTrait`,
since the application is responsible for keeping shortenings.
:::

:::{php:method} getElement($shortName)
Given a short-name of the element, will return the object. Throws exception
if object with such shortName does not exist.
:::

:::{php:method} hasElement($shortName)
Given a short-name of the element, will return true if object with
such shortName exists, otherwise false.
:::

:::{php:method} _uniqueElementName
Internal method to create unique name for an element.
:::

## Trackable Trait

:::{php:trait} TrackableTrait
Trackable trait implements a few fields for the object that will maintain
it's relationship with the owner (parent).

When name is set for container, then all children will derive their names
of the parent.

* Parent: foo
* Child: foo_child1

The name will be unique within this container.
:::

### Properties

:::{php:attr} owner
Will point to object which has add()ed this object. If multiple objects
have added this object, then this will point to the most recent one.
:::

:::{php:attr} shortName
When you add item into the owner, the "shortName" will contain short name
of this item.
:::

### Methods

:::{php:method} getDesiredName
Normally object will try to be named after it's class, if the name is omitted.
You can override this method to implement a different mechanics.

If you pass 'desired_name' => 'heh' to a constructor, then it will affect the
preferred name returned by this method. Unlike 'name' => 'heh' it won't fail
if another element with this name exists, but will add '_2' postfix.
:::

:::{php:method} destroy
If object owner is set, then this will remove object from it's owner elements
reducing number of links to the object. Normally PHP's garbage collector
should remove object as soon as number of links is zero.
:::
