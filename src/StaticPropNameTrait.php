<?php

declare(strict_types=1);

namespace Atk4\Core;

use Mvorisek\Atk4\Hintable\Core\MagicProp;
use Mvorisek\Atk4\Hintable\Core\Prop;

trait StaticPropNameTrait
{
    /**
     * Returns a magic class that pretends to be instance of this class, but in reality
     * any property returns its name.
     *
     * @return static
     *
     * @phpstan-return MagicProp<static, string>
     */
    public static function propName()// :static supported by PHP8+
    {
        return Prop::propName(static::class); // @phpstan-ignore-line
    }
}
