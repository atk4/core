<?php

// vim:ts=4:sw=4:et:fdm=marker

namespace atk4\core;

/**
 * All exceptions generated by Agile Core will use this class.
 *
 * @license MIT
 * @copyright Agile Toolkit (c) http://agiletoolkit.org/
 */
class Exception extends \Exception
{
    /**
     * Most exceptions would be a cause by some other exception, Agile
     * Core will encapsulate them and allow you to access them anyway.
     */
    private $params = [];

    /** @var array */
    public $trace2; // because PHP's use of final() sucks!

    /**
     * Constructor.
     *
     * @param string|array  $message
     * @param int           $code
     * @param \Exception    $previous
     */
    public function __construct(
        $message = '',
        $code = 0,
        /* \Throwable */ $previous = null
    ) {
        if (is_array($message)) {
            // message contain additional parameters
            $this->params = $message;
            $message = array_shift($this->params);
        }

        parent::__construct($message, $code, $previous);
        $this->trace2 = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
    }

    /**
     * Change message (subject) of a current exception. Primary use is
     * for localization purposes.
     *
     * @param string $message
     *
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Return trace array.
     *
     * @return array
     */
    public function getMyTrace()
    {
        return $this->trace2;
    }

    /**
     * Output exception message using color sequences.
     *
     * <exception name>: <string>
     * <info>
     *
     * trace
     *
     * --
     * <triggered by>
     *
     * @return string
     */
    public function getColorfulText()
    {
        $output = "\033[1;31m--[ Agile Toolkit Exception ]---------------------------\n";
        $output .= get_class($this).": \033[47m".$this->getMessage()."\033[0;31m".
            ($this->getCode() ? ' [code: '.$this->getCode().']' : '');

        foreach ($this->params as $key => $val) {
            $key = str_pad($key, 19, ' ', STR_PAD_LEFT);
            $output .= "\n".$key.': '.$this->toString($val);
        }

        $output .= "\n\033[0mStack Trace: ";

        $in_atk = true;
        $escape_frame = false;

        foreach ($this->getMyTrace() as $call) {
            if (!isset($call['file'])) {
                $call['file'] = '';
            } elseif (
                $in_atk &&
                strpos($call['file'], '/data/src/') === false &&
                strpos($call['file'], '/core/src/') === false &&
                strpos($call['file'], '/dsql/src/') === false
            ) {
                $escape_frame = true;
                $in_atk = false;
            }

            $file = str_pad(substr($call['file'], -40), 40, ' ', STR_PAD_LEFT);

            $line = str_pad(@$call['line'], 4, ' ', STR_PAD_LEFT);

            $output .= "\n\033[0;34m".$file."\033[0m";
            $output .= ":\033[0;31m".$line."\033[0m";

            if (isset($call['object'])) {
                $name = (!isset($call['object']->name)) ? get_class($call['object']) : $call['object']->name;
                $output .= " - \033[0;32m".$name."\033[0m";
            }

            $output .= " \033[0;32m";

            if (isset($call['class'])) {
                $output .= $call['class'].'::';
            }

            if ($escape_frame) {
                $output .= "\033[0,31m".$call['function'];
                $escape_frame = false;

                $args = [];
                foreach ($call['args'] as $arg) {
                    $args[] = $this->toString($arg);
                }

                $output .= "\n".str_repeat(' ', 20)."\033[0,31m(".implode(', ', $args);
            } else {
                $output .= "\033[0,33m".$call['function'].'(';
            }

            $output .= ')';
        }

        if ($p = $this->getPrevious()) {
            $output .= "\n\033[0mCaused by Previous Exception:\n";
            $output .= "\033[1;31m".get_class($p).': '.$p->getMessage()."\033[0;31m".
                ($p->getCode() ? ' [code: '.$p->getCode().']' : '');
            if ($p instanceof \atk4\core\Exception) {
                $output .= "\n+".str_replace("\n", "\n| ", $p->getColorfulText());
            }
        }

        // next print params

        $output .= "\n\033[1;31m-------------------------------------------------------\n";

        return $output."\033[0m";
    }

    /**
     * Similar to getColorfulText() but will use raw HTML for outputting colors.
     *
     * @return string
     */
    public function getHTMLText()
    {
        $output = "--[ Agile Toolkit Exception ]---------------------------\n";
        $output .= get_class($this).": <font color='pink'><b>".$this->getMessage().'</b></font>'.
            ($this->getCode() ? ' [code: '.$this->getCode().']' : '');

        foreach ($this->params as $key => $val) {
            $key = str_pad($key, 19, ' ', STR_PAD_LEFT);
            $output .= "\n".$key.': '.$this->toString($val);
        }

        $output .= "\nStack Trace: ";

        $in_atk = true;
        $escape_frame = false;

        foreach ($this->getMyTrace() as $call) {
            if (!isset($call['file'])) {
                $call['file'] = '';
            } elseif (
                $in_atk &&
                strpos($call['file'], '/data/src/') === false &&
                strpos($call['file'], '/core/src/') === false &&
                strpos($call['file'], '/dsql/src/') === false
            ) {
                $escape_frame = true;
                $in_atk = false;
            }

            $file = str_pad(substr($call['file'], -40), 40, ' ', STR_PAD_LEFT);

            $line = str_pad(@$call['line'], 4, ' ', STR_PAD_LEFT);

            $output .= "\n<font color='cyan'>".$file.'</font>';
            $output .= ":<font color='pink'>".$line.'</font>';

            if (isset($call['object'])) {
                $name = (!isset($call['object']->name)) ? get_class($call['object']) : $call['object']->name;
                $output .= " - <font color='yellow'>".$name.'</font>';
            }

            $output .= " <font color='gray'>";

            if (isset($call['class'])) {
                $output .= $call['class'].'::';
            }
            $output .= '</font>';

            if ($escape_frame) {
                $output .= "<font color='pink'>".$call['function'].'</font>';
                $escape_frame = false;

                $args = [];
                foreach ($call['args'] as $arg) {
                    $args[] = $this->toString($arg);
                }

                $output .= "\n".str_repeat(' ', 20)."<font color='pink'>(".implode(', ', $args);
            } else {
                $output .= "<font color='gray'>".$call['function'].'(';
            }

            $output .= ')</font>';
        }

        if ($p = $this->getPrevious()) {
            $output .= "\n\nCaused by Previous Exception:\n";
            $output .= get_class($p).": <font color='pink'>".$p->getMessage().'</font>'.
                ($p->getCode() ? ' [code: '.$p->getCode().']' : '');
        }

        // next print params

        $output .= "\n--------------------------------------------------------\n";

        return $output;
    }

    /**
     * Output exception message using HTML block and Semantic UI formatting. It's your job
     * to put it inside boilerplate HTML and output, e.g:.
     *
     *   $l = new \atk4\ui\App();
     *   $l->initLayout('Centered');
     *   $l->layout->template->setHTML('Content', $e->getHTML());
     *   $l->run();
     *   exit;
     *
     * @return string
     */
    public function getHTML()
    {
        $output = '<div class="ui negative icon message"><i class="warning sign icon"></i><div class="content"><div class="header">Fatal Error</div>';
        $output .= get_class($this).': '.$this->getMessage().
            ($this->getCode() ? ' <div class="ui small yellow label">Code<div class="detail">'.$this->getCode().'</div></div>' : '');
        $output .= '</div>'; // content
        $output .= '</div>';

        if ($this->params) {
            $output .= '<div class="ui top attached segment">';
            $output .= '<div class="ui top attached label">Exception Parameters</div>';
            $output .= '<ul class="list">';

            foreach ($this->params as $key => $val) {
                $key = str_pad($key, 19, ' ', STR_PAD_LEFT);
                $output .= '<li><b>'.htmlentities($key).'</b>: '.htmlentities($this->toString($val)).'</li>';
            }

            $output .= '</ul>';
            $output .= '</div>';
        }

        $output .= '<div class="ui top attached segment">';
        $output .= '<div class="ui top attached label">Stack Trace</div>';
        $output .= '<table class="ui very compact small selectable table">';
        $output .= '<thead><tr><th>File</th><th>Object</th><th>Method</th></tr></thead><tbody>';

        $in_atk = true;
        $escape_frame = false;

        foreach ($this->getMyTrace() as $call) {
            if (!isset($call['file'])) {
                $call['file'] = '';
            } elseif (
                $in_atk &&
                strpos($call['file'], '/data/src/') === false &&
                strpos($call['file'], '/core/src/') === false &&
                strpos($call['file'], '/dsql/src/') === false
            ) {
                $escape_frame = true;
                $in_atk = false;
            }

            $file = str_pad(substr($call['file'], -40), 40, ' ', STR_PAD_LEFT);

            $line = str_pad(@$call['line'], 4, ' ', STR_PAD_LEFT);

            if ($escape_frame) {
                $output .= "<tr class='negative'><td>".$file;
            } else {
                $output .= '<tr><td>'.$file;
            }
            $output .= ':'.$line.'</td><td>';

            if (isset($call['object'])) {
                $name = (!isset($call['object']->name)) ? get_class($call['object']) : $call['object']->name;
                $output .= $name;
            } else {
                $output .= '-';
            }

            $output .= '</td><td>';

            if (isset($call['class'])) {
                $output .= $call['class'].'::';
            }

            if ($escape_frame) {
                $output .= $call['function'];
                $escape_frame = false;

                $args = [];
                foreach ($call['args'] as $arg) {
                    $args[] = $this->toString($arg);
                }

                $output .= "</td></tr><tr class='negative'><td colspan=2></td><td> (".str_repeat(' ', 20).implode(', ', $args).')';
            } else {
                $output .= $call['function'].'()';
            }

            $output .= '</td></tr>';
        }

        $output .= '</tbody></table>';
        $output .= '</div>';

        if ($p = $this->getPrevious()) {
            $output .= '<div class="ui top attached segment">';
            $output .= '<div class="ui top attached label">Caused by Previous Exception:</div>';

            if ($p instanceof \atk4\core\Exception) {
                $output .= $p->getHTML();
            } else {
                //$output .= "\033[1;31m".get_class($p).': '.$p->getMessage()."\033[0;31m".
                //($p->getCode() ? ' [code: '.$p->getCode().']' : '');

                $output .= '<div class="ui negative icon message"><i class="warning sign icon"></i><div class="content"><div class="header">Fatal Error</div>';
                $output .= get_class($p).': '.$p->getMessage().
                    ($p->getCode() ? ' <div class="ui small yellow label">Code<div class="detail">'.$p->getCode().'</div></div>' : '');
                $output .= '</div>'; // content
                $output .= '</div>';
            }

            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Safely converts some value to string.
     *
     * @param mixed $val
     *
     * @return string
     */
    public function toString($val)
    {
        if (is_object($val) && !$val instanceof \Closure) {
            if (isset($val->_trackableTrait)) {
                $name = isset($val->name) ? $val->name : '';
                return get_class($val).' ('.$name.')';
            }

            return 'Object '.get_class($val);
        }

        return (string) json_encode($val);
    }

    /**
     * Follow the getter-style of PHP Exception.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Augment existing exception with more info.
     *
     * @param string $param
     * @param mixed  $value
     *
     * @return $this
     */
    public function addMoreInfo($param, $value)
    {
        $this->params[$param] = $value;

        return $this;
    }
}
