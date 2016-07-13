## 1.1.0

If any of your objects use ContainerTrait without TrackableTrait, you may need to update
your code to avoid warnings.

* Significant updates to documentation
* ContainerTrait no longer add 'name' property. [Use it with TrackableTrait or NameTrait](http://agile-core.readthedocs.io/en/develop/container.html?highlight=nametrait#name-trait)
* Coding style improved

## 1.0.3

* Minor cleanups, exception will now show previous exception

## 1.0.2

* Added FactoryTrait
* FactoryTrait has normalizeClassName method

## 1.0.1

* Exception->getColorfulText now will provide console-friendly colorful text
* Hooks can now receive references
* property $elements is now public

## 1.0

* implemented Exception, that supports additional parameters
* implemented ContainerTrait, add one object inside another
* implemented TrackableTrait, being able to find objects within container
* implemented InitializerTrait, objects will call their init() method
* implemented AppScopeTrait, objects will keep $this->app linked with the application
* implemented HookTrait, can add and execute hooks
* implemented DynamicMethod, can register methods dynamically and execute through catch-all

## 0.1

* Initial Release
* Bootstraped Documentation (sphinx-doc)
* Initial test-suite and concept description
