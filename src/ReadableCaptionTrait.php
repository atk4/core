<?php

declare(strict_types=1);

namespace atk4\core;

trait ReadableCaptionTrait
{
    /**
     * Generates human readable caption from camelCase model class name or field names.
     *
     * This will translate 'this\\ _isNASA_MyBigBull shit_123\Foo'
     * into 'This Is NASA My Big Bull Shit 123 Foo'
     */
    public function readableCaption(string $s): string
    {
        //$s = 'this\\ _isNASA_MyBigBull shit_123\Foo';

        // first remove not allowed characters and uppercase words
        $s = ucwords(preg_replace('/[^a-z0-9]+/i', ' ', $s));

        // and then run regex to split camelcased words too
        $s = array_map('trim', preg_split('/^[^A-Z\d]+\K|[A-Z\d][^A-Z\d]+\K/', $s, -1, PREG_SPLIT_NO_EMPTY));
        $s = implode(' ', $s);

        return $s;
    }
}
