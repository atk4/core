<?php

namespace atk4\core;

trait SessionTrait
{
    /**
     * Check this property to see if trait is present in the object.
     *
     * @var bool
     */
    public $_sessionTrait = true;

    /**
     * Session container key.
     *
     * @var string
     */
    protected $session_key = 'o';

    /**
     * Create new session.
     *
     * @param array $options Options for session_start()
     */
    public function startSession($options = [])
    {
        // all methods use this method to start session, so we better check
        // NameTrait existence here in one place.
        if (!isset($this->_nameTrait)) {
            throw new Exception(['Object should have NameTrait applied to use session']);
        }

        switch (session_status()) {
            case PHP_SESSION_DISABLED:
                // @codeCoverageIgnoreStart - impossible to test
                throw new Exception(['Sessions are disabled on server']);
                // @codeCoverageIgnoreEnd
                break;
            case PHP_SESSION_NONE:
                session_start($options);
                break;
        }
    }

    /**
     * Destroy existing session.
     */
    public function destroySession()
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
            unset($_SESSION);
        }
    }

    /**
     * Remember data in object-relevant session data.
     *
     * @param string $key   Key for the data
     * @param mixed  $value Value
     *
     * @return mixed $value
     */
    public function memorize($key, $value)
    {
        $this->startSession();

        if (is_callable($value)) {
            $value = call_user_func($value, $key);
        }

        $_SESSION[$this->session_key][$this->name][$key] = $value;

        return $value;
    }

    /**
     * Similar to memorize, but if value for key exist, will return it.
     *
     * @param string $key     Data Key
     * @param mixed  $default Default value
     *
     * @return mixed Previously memorized data or $default
     */
    public function learn($key, $default = null)
    {
        $this->startSession();

        if (!isset($_SESSION[$this->session_key][$this->name][$key])
            || is_null($_SESSION[$this->session_key][$this->name][$key])
        ) {
            return $this->memorize($key, $default);
        } else {
            return $this->recall($key);
        }
    }

    /**
     * Returns session data for this object. If not previously set, then
     * $default is returned.
     *
     * @param string $key     Data Key
     * @param mixed  $default Default value
     *
     * @return mixed Previously memorized data or $default
     */
    public function recall($key, $default = null)
    {
        $this->startSession();

        if (!isset($_SESSION[$this->session_key][$this->name][$key])
            || is_null($_SESSION[$this->session_key][$this->name][$key])
        ) {
            if (is_callable($default)) {
                $default = call_user_func($default, $key);
            }

            return $default;
        }

        return $_SESSION[$this->session_key][$this->name][$key];
    }

    /**
     * Forget session data for $key. If $key is omitted will forget all
     * associated session data.
     *
     * @param string $key Optional key of data to forget
     *
     * @return $this
     */
    public function forget($key = null)
    {
        $this->startSession();

        if ($key === null) {
            unset($_SESSION[$this->session_key][$this->name]);
        } else {
            unset($_SESSION[$this->session_key][$this->name][$key]);
        }

        return $this;
    }
}
