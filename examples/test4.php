<?php

require '../vendor/autoload.php';

use atk4\core\Exception;

function faulty($test)
{
    if ($test > 5) {
        throw new Exception([
            'Test value is too high',
            'test' => $test,
        ]);
    }

    return faulty($test + 1);
}

try {
    faulty(1);
} catch (Exception $e) {
    echo $e->getColorfulText();
}
