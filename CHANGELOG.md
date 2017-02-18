## 1.1.11

- Display object with the exception dump. #38 

## 1.1.10

 - prevent looping by calling normalizeClass in the API that may also
   implement factory trait and cause a loop

## 1.1.8

 - Added Exception::getHTML(), similar to getColofulException but for
   HTML output
 - Improved handling of unique elements with "desired_name" property
 - Will route 'normalizeClassName' to your application now
 - Various type-hinting improvements and making IDEs happier

## 1.1.8

Fixed minor bug, for objects that have no elements at all

## 1.1.7

 - Added some support files for Psysh (e command) #29
 - Improve debug backtrace by including object references #28
 - implement addMoreInfo for exceptions #27

## 1.1.6

Added some support files for PHPUnit

## 1.1.4

Improved DebugTrait:

 - added debugTraceChange
 - added better integration with app scope
 - added exception::addMoreInfo
 - added documentation for Exception

## 1.1.3

Added draft implementation for Session trait. Minor cleanups.

## 1.1.2

Mostly new tests and improvements in documentation.

## 1.1.1

Focus on adding documentation for existing features, improving comments
and adding examples. 
* src/Object is removed as it wasn't used anywhere.
* added lots of test-scripts

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
