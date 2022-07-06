<?php

declare(strict_types=1);

namespace Atk4\Core;

trait ReadableCaptionTrait
{
    /**
     * Generates human readable caption from camelCase model class name or field names.
     *
     * This will translate 'this\ _isNASA_MyBigBull shit_123\Foo' into 'This Is NASA My Big Bull Shit 123 Foo'.
     */
    public function readableCaption(string $s): string
    {
        // first remove not allowed characters and uppercase words
        $s = ucwords(preg_replace('~[^a-z\d]+~i', ' ', $s));

        // and then run regex to split camelcased words too
        $s = array_map('trim', preg_split('~(?:^|[A-Z\d])[^A-Z\d]+\K~', $s, -1, \PREG_SPLIT_NO_EMPTY));
        $s = implode(' ', $s);

        // replace "Id" with "ID"
        $s = preg_replace('~(?<=^| )Id~', 'ID', $s);

        return $s;
    }
}
