# Agile Core - Trait collection for PHP frameworks

**Agile Core is a collection of PHP Traits for designing object-oriented frameworks**

Code Quality:

[![Build Status](https://travis-ci.org/atk4/core.png?branch=develop)](https://travis-ci.org/atk4/core)
[![Code Climate](https://codeclimate.com/github/atk4/core/badges/gpa.svg)](https://codeclimate.com/github/atk4/core)
[![StyleCI](https://styleci.io/repos/57242416/shield)](https://styleci.io/repos/57242416)
[![Test Coverage](https://codeclimate.com/github/atk4/core/badges/coverage.svg)](https://codeclimate.com/github/atk4/core/coverage)
[![Issue Count](https://codeclimate.com/github/atk4/core/badges/issue_count.svg)](https://codeclimate.com/github/atk4/core)

Resources and Community:

[![Documentation Status](https://readthedocs.org/projects/agile-core/badge/?version=develop)](http://agile-core.readthedocs.io/en/develop/?badge=develop)
[![Gitter](https://img.shields.io/gitter/room/atk4/data.svg?maxAge=2592000)](https://gitter.im/atk4/dataset?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Stack Overlfow Community](https://img.shields.io/stackexchange/stackoverflow/t/atk4.svg?maxAge=2592000)](http://stackoverflow.com/questions/ask?tags=atk4)
[![Discord User forum](https://img.shields.io/badge/discord-User_Forum-green.svg)](https://forum.agiletoolkit.org/c/44)

Stats:

[![License](https://poser.pugx.org/atk4/core/license)](https://packagist.org/packages/atk4/core)
[![GitHub release](https://img.shields.io/github/release/atk4/core.svg?maxAge=2592000)](https://packagist.org/packages/atk4/core)

Working on your own framework? Here you can find some useful and ligthweight traits for your code:

 - Containers: Implements Parent/Child relations between your objects
 - Hooks: Create hooks and register call-backs with priorities and arguments
 - Initializers: Automatically execute init() method of your object
 - Dynamic Methods: Add methods dynamically into existing objects
 - Factory: Specify class name as a string
 - App Scope: Inject global "app" object and pass it to new objects
 
## Install from Composer

```
composer require atk4/core
```

## Sample Use

Containers:

```
class MyParentObject {
    use \atk4\core\ContainerTrait;
}


class MyChildClass {
    use \atk4\core\TrackableTrait;
}


$parent = new MyParentObject;

$parent->add(new MyChildClass(), 'foo-bar');

var_dump( $parent->getElement('foo-bar') );
```

Hooks:

```
class MyClass {
    use \atk4\core\HookTrait;

    public function doWork()
    {
        $this->hook('beforeWork');
        
        echo "Doing work\n";
        
        $this->hook('afterWork');
    }
}


$c = new MyClass();
$c->addHook('afterWork', function() { 
    echo "HOOKed on work\n"; 
});
$c->doWork();
```

Dynamic Methods:

```
class MyClass {
    use \atk4\core\DynamicMethodTrait;
    use \atk4\core\HookTrait;
}

$c = new MyClass()

$c->addMethod('mymethod', function($c, $a, $b){
    return $a+$b;
});


echo $c->mymethod(2,3)."\n";
```

Core Exception:

```
use atk4\core\Exception;

function faulty($test) {
    if ($test>5) {
        throw new Exception([
            'Test value is too high',
            'test'=>$test
        ]);
    }

    return faulty($test+1);
}


try {
    faulty(1);
} catch (Exception $e) {
    echo $e->getColorfulText();
}
```

For full documentation: [http://agile-core.readthedocs.io/](http://agile-core.readthedocs.io/)


## Planned Features

 - Implement Debug
 - Implement QuickException
 - Implement Sessions (for MVC frameworks)
 - Implement Renderable and Template (for UI frameworks)

## Past Updates

* 15 Jul: Improved test coverage, updated readme
* 04 Jul: Implemented FactoryTrait
* 21 May: Released 1.0: Implemented ContainerTrait, Trackable, Initializer, AppScope, Hooks, DynamicMethod
* 11 May: Released 0.1
* 11 May: Finished basic docs
* 27 Apr: Initial Commit

