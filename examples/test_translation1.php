<?php

use atk4\core\Exception;
use atk4\core\Translator\Translator;
use atk4\data\Persistence;

require '../vendor/autoload.php';

$trans = Translator::instance();
$trans->setDefaultLocale('ru');

try {
    Persistence::connect('error:error');
} catch (\Throwable $e) {
    /* @var $e Exception */
    $e->translate();
    echo $e->getColorfulText();
}
