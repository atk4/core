<?php

use atk4\core\Exception;
use atk4\core\Translator\Translator;

require '../vendor/autoload.php';

if (class_exists('atk4\data\Persistence')) {
    try {
        \atk4\data\Persistence::connect('error:error');
    } catch (Exception $e) {
        echo $e->getColorfulText();
    }

    $trans = Translator::instance();
    $trans->setDefaultLocale('ru');

    try {
        \atk4\data\Persistence::connect('error:error');
    } catch (Exception $e) {
        echo $e->getColorfulText();
    }
}
