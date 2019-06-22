<?php

namespace atk4\core;

use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

/**
 * If a class use this trait, string can be translated calling method translate.
 */
trait TranslatableTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_translatableTrait = true;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getTranslator() : ?TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Translates the given message.
     *
     * When a number is provided as a parameter named "%count%", the message is parsed for plural
     * forms and a translation is chosen according to this number using the following rules:
     *
     * Given a message with different plural translations separated by a
     * pipe (|), this method returns the correct portion of the message based
     * on the given number, locale and the pluralization rules in the message
     * itself.
     *
     * The message supports two different types of pluralization rules:
     *
     * interval: {0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples
     * indexed:  There is one apple|There are %count% apples
     *
     * The indexed solution can also contain labels (e.g. one: There is one apple).
     * This is purely for making the translations more clear - it does not
     * affect the functionality.
     *
     * The two methods can also be mixed:
     *     {0} There are no apples|one: There is one apple|more: There are %count% apples
     *
     * An interval can represent a finite set of numbers:
     *  {1,2,3,4}
     *
     * An interval can represent numbers between two numbers:
     *  [1, +Inf]
     *  ]-1,2[
     *
     * The left delimiter can be [ (inclusive) or ] (exclusive).
     * The right delimiter can be [ (exclusive) or ] (inclusive).
     * Beside numbers, you can use -Inf and +Inf for the infinite.
     *
     * @see https://en.wikipedia.org/wiki/ISO_31-11
     *
     * @param string      $id         The message id (may also be an object that can be cast to string)
     * @param array       $parameters An array of parameters for the message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     *
     * @return string The translated string
     */
    public function _($id, array $parameters = [], $domain = null, $locale = null) :string
    {

        // case if is child which use AppScope + TranslatableTrait
        if (isset($this->app) && $this->app !== $this && method_exists($this->app, '_')) {
            return $this->app->_($id, $parameters, $domain, $locale);
        }

        // case if use TranslatableTrait
        if (isset($this->translator) && $this->translator instanceof TranslatorInterface) {
            return $this->translator->trans($id, $parameters, $domain, $locale);
        }

        // FALLBACK : Replicate internal library functionality
        // if not use TranslatableTrait and is not a Child of App which use TranslatableTrait
        $translator = new class() implements TranslatorInterface {
            use TranslatorTrait;
        };

        return $translator->trans($id, $parameters, $domain, $locale);
    }
}
