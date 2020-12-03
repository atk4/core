<?php

declare(strict_types=1);

use Atk4\Core\Exception;
use Atk4\Core\Translator\Translator;

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
