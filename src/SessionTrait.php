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
     * Create new session.
     *
     * @param array $options Options for session_start()
     */
    public function startSession($options = [])
    {
        // all methods use this method to start session, so we better check
        // NameTrait existance here in one place.
        if (!isset($this->_nameTrait)) {
            throw new Exception(['Object should have NameTrait applied to use session']);
        }

        switch (session_status()) {
            case PHP_SESSION_DISABLED:
                throw new Exception(['Sessions are disabled on server']);
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

        if (is_object($value)) {
            unset($_SESSION['o'][$this->name][$key]);
            $_SESSION['s'][$this->name][$key] = serialize($value);

            return $value;
        }

        unset($_SESSION['s'][$this->name][$key]);
        $_SESSION['o'][$this->name][$key] = $value;

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

        if (!isset($_SESSION['o'][$this->name][$key])
            || is_null($_SESSION['o'][$this->name][$key])
        ) {
            if (is_callable($default)) {
                $default = call_user_func($default);
            }

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

        if (!isset($_SESSION['o'][$this->name][$key])
            || is_null($_SESSION['o'][$this->name][$key])
        ) {
            if (!isset($_SESSION['s'][$this->name][$key])) {
                return $default;
            }

            if (isset($this->_containerTrait)) {
                $v = $this->add(unserialize($_SESSION['s'][$this->name][$key]));
                if (isset($v->_initializerTrait)) {
                    $v->init();
                }

                return $v;
            }

            return unserialize($_SESSION['s'][$this->name][$key]);
        }

        return $_SESSION['o'][$this->name][$key];
    }

   /**
     * Forget session data for arg $key. If $key is omitted will forget all
     * associated session data.
     *
     * @param string $key Optional key of data to forget
     *
     * @return $this
     */
    public function forget($key = null)
    {
        $this->startSession();

        // Prevent notice generation when using custom session handler
        if (!isset($_SESSION)) {
            return $this;
        }

        if (is_null($key)) {
            unset($_SESSION['o'][$this->name]);
            unset($_SESSION['s'][$this->name]);
        } else {
            unset($_SESSION['o'][$this->name][$key]);
            unset($_SESSION['s'][$this->name][$key]);
        }

        return $this;
    }



    /**
     * Initializes existing or new session.
     *
     * Attempts to re-initialize session. If session is not found,
     * new one will be created, unless $create is set to false. Avoiding
     * session creation and placing cookies is to enhance user privacy.
     * Call to memorize() / recall() will automatically create session
     *
     * @param bool $create
     */
    /*
    public $_is_session_initialized = false;
    public function initializeSession($create = true)
    {
        if ($this->_is_session_initialized || session_id()) {
            return;
        }

        // Change settings if defined in settings file
        $params = session_get_cookie_params();

        $params['httponly'] = true;   // true by default

        foreach ($params as $key => $default) {
            $params[$key] = $this->app->getConfig('session/'.$key, $default);
        }

        if ($create === false && !isset($_COOKIE[$this->name])) {
            return;
        }
        $this->_is_session_initialized = true;
        session_set_cookie_params(
            $params['lifetime'],
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
        session_name($this->name);
        session_start();
    }
    */

    /**
     * Completely destroy existing session.
     */
    /*
    public function destroySession()
    {
        if ($this->_is_session_initialized) {
            $_SESSION = array();
            if (isset($_COOKIE[$this->name])) {
                setcookie($this->name/*session_name()*//*, '', time() - 42000, '/');
            }
            session_destroy();
            $this->_is_session_initialized = false;
        }
    }
    */

    /**
     * Remember data in object-relevant session data.
     *
     * @param string $key   Key for the data
     * @param mixed  $value Value
     *
     * @return mixed $value
     */
    /*
    public function memorize($key, $value)
    {
        if (!session_id()) {
            $this->initializeSession();
        }

        if ($value instanceof Model) {
            unset($_SESSION['o'][$this->name][$key]);
            $_SESSION['s'][$this->name][$key] = serialize($value);

            return $value;
        }

        unset($_SESSION['s'][$this->name][$key]);
        $_SESSION['o'][$this->name][$key] = $value;

        return $value;
    }
    */

    /**
     * Similar to memorize, but if value for key exist, will return it.
     *
     * @param string $key     Data Key
     * @param mixed  $default Default value
     *
     * @return mixed Previously memorized data or $default
     */
    /*
    public function learn($key, $default = null)
    {
        if (!session_id()) {
            $this->initializeSession(false);
        }

        if (!isset($_SESSION['o'][$this->name][$key])
            || is_null($_SESSION['o'][$this->name][$key])
        ) {
            if (is_callable($default)) {
                $default = call_user_func($default);
            }

            return $this->memorize($key, $default);
        } else {
            return $this->recall($key);
        }
    }
    */

    /**
     * Forget session data for arg $key. If $key is omitted will forget all
     * associated session data.
     *
     * @param string $key Optional key of data to forget
     *
     * @return $this
     */
    /*
    public function forget($key = null)
    {
        if (!session_id()) {
            $this->initializeSession(false);
        }

        // Prevent notice generation when using custom session handler
        if (!isset($_SESSION)) {
            return $this;
        }

        if (is_null($key)) {
            unset($_SESSION['o'][$this->name]);
            unset($_SESSION['s'][$this->name]);
        } else {
            unset($_SESSION['o'][$this->name][$key]);
            unset($_SESSION['s'][$this->name][$key]);
        }

        return $this;
    }
    */

    /**
     * Returns session data for this object. If not previously set, then
     * $default is returned.
     *
     * @param string $key     Data Key
     * @param mixed  $default Default value
     *
     * @return mixed Previously memorized data or $default
     */
    /*
    public function recall($key, $default = null)
    {
        if (!session_id()) {
            $this->initializeSession(false);
        }

        if (!isset($_SESSION['o'][$this->name][$key])
            || is_null($_SESSION['o'][$this->name][$key])
        ) {
            if (!isset($_SESSION['s'][$this->name][$key])) {
                return $default;
            }
            $v = $this->add(unserialize($_SESSION['s'][$this->name][$key]));
            $v->init();

            return $v;
        }

        return $_SESSION['o'][$this->name][$key];
    }
    */
}
