<?php

namespace atk4\core;

trait AppScopeTrait {

    public $_appScope = true;

    /**
     * Always points to current Application
     *
     * @var App_CLI
     */
    public $app;

    /**
     * Object in Agile Toolkit contain $name property which is derrived from
     * the owher object and keeps extending as you add objects deeper into
     * run-time tree. Sometimes that may generate long names. Long names are
     * difficult to read, they increase HTML output size but most importantly
     * they may be restricted by security extensions such as SUHOSIN.
     *
     * Agile Toolkit implements a mechanism which will replace common beginning
     * of objects with an abbreviation thus keeping object name length under
     * control. This variable defines the maximum length of the object's $name.
     * Be mindful that some objects will concatinate theri name with fields,
     * so the maximum letgth of GET argument names can exceed this value by
     * the length of your field.
     *
     * We recommend you to increase SUHOSIN get limits if you encounter any
     * problems. Set this value to "false" to turn off name shortening.
     *
     * @var int
     */
    public $max_name_length = 60;

    /**
     * As more names are shortened, the substituted part is being placed into
     * this hash and the value contains the new key. This helps to avoid creating
     * many sequential prefixes for the same character sequenece.
     *
     * @var array
     */
    public $unique_hashes = array();
}
