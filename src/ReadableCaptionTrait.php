<?php

namespace atk4\core;

/**
 * @deprecated use Utils::toReadableCaption instead
 *
 */
trait ReadableCaptionTrait
{
    /**
     * Generates human readable caption from camelCase model class name or field names.
     *
     * This will translate 'this\\ _isNASA_MyBigBull shit_123\Foo'
     * into 'This Is NASA My Big Bull Shit 123 Foo'
     *
     * @param string $string
     *
     * @return string
     */
    public function readableCaption($string)
    {
        return Utils::toReadableCaption($string);
    }
}
