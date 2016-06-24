
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
