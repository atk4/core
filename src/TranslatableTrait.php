<?php

namespace atk4\core;

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

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * Set Translator.
     *
     * @TODO should be setted internally or leave it public and leave it definable via Defaults?
     *
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Translate a string.
     *
     * @param string $string
     * @param mixed  ...$args
     *
     * @throws Exception
     *
     * @return string
     */
    public function __(string $string, ...$args)
    {
        $count_args = count($args);
        // translation : simple case
        if (0 === $count_args) {
            return $this->translator->translate($string);
        }

        // translation : plural case
        if (1 === count($args) && is_numeric($args[0])) {
            return $this->translator->translate_plural($string, $args[0]);
        }

        $translated_args = [];
        $translated_args[] = $this->translator->translate($string);
        foreach ($args as $sub_args) {
            if (!is_array($sub_args)) {
                $sub_args = [$sub_args];
            }

            $sub_string = $sub_args[0];
            $sub_args = $sub_args[1] ?? false;

            if (!$sub_args) {
                $translated_args[] = $this->__($sub_string);
            } else {
                $translated_args[] = $this->__($sub_string, $sub_args);
            }
        }

        return call_user_func_array('sprintf', $translated_args);
    }
}
