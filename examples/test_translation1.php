<?php

declare(strict_types=1);

use Atk4\Core\Exception;
use Atk4\Core\Translator\Translator;

require '../vendor/autoload.php';

if (class_exists('Atk4\Data\Persistence')) {
    try {
        \Atk4\Data\Persistence::connect('error:error');
    } catch (Exception $e) {
        echo $e->getColorfulText();
    }

    $trans = Translator::instance();
    $trans->setDefaultLocale('ru');

    try {
        \Atk4\Data\Persistence::connect('error:error');
    } catch (Exception $e) {
        echo $e->getColorfulText();
    }
}
