<?php

declare(strict_types=1);

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
    protected $session_key = '__atk_session';

    /**
     * Create new session.
     *
     * @param array $options Options for session_start()
     */
    public function startSession(array $options = [])
    {
        // all methods use this method to start session, so we better check
        // NameTrait existence here in one place.
        if (!isset($this->_nameTrait)) {
            throw new Exception('Object should have NameTrait applied to use session');
        }

        switch (session_status()) {
            case PHP_SESSION_DISABLED:
                // @codeCoverageIgnoreStart - impossible to test
                throw new Exception('Sessions are disabled on server');
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
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
            unset($_SESSION);
        }
    }

    /**
     * Remember data in object-relevant session data.
     *
     * @param mixed $value Value
     *
     * @return mixed $value
     */
    public function memorize(string $key, $value)
    {
        $this->startSession();

        $_SESSION[$this->session_key][$this->name][$key] = $value;

        return $value;
    }

    /**
     * Similar to memorize, but if value for key exist, will return it.
     *
     * @return mixed Previously memorized data or $default
     */
    public function learn(string $key, $default = null)
    {
        $this->startSession();

        if (!isset($_SESSION[$this->session_key][$this->name][$key])
            || $_SESSION[$this->session_key][$this->name][$key] === null
        ) {
            if (is_callable($default)) {
                if (!$default instanceof \Closure) {
                    throw new Exception('Callable must be an instance of Closure');
                }

                $default = $default($key);
            }

            return $this->memorize($key, $default);
        }

        return $this->recall($key);
    }

    /**
     * Returns session data for this object. If not previously set, then
     * $default is returned.
     *
     * @return mixed Previously memorized data or $default
     */
    public function recall(string $key, $default = null)
    {
        $this->startSession();

        if (!isset($_SESSION[$this->session_key][$this->name][$key])
            || $_SESSION[$this->session_key][$this->name][$key] === null
        ) {
            if (is_callable($default)) {
                if (!$default instanceof \Closure) {
                    throw new Exception('Callable must be an instance of Closure');
                }
                $default = $default($key);
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
    public function forget(string $key = null)
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
