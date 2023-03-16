<?php

namespace WHMCSExpert\mtLibs\exceptions;

use WHMCSExpert as main;

/**
 * Base Module Exception
 *
 * Use as base for other exceptions
 *
 */
class Base extends \Exception
{
    private $_token;

    public function __construct($message, $code, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->_token = md5(microtime());
    }

    public function getToken()
    {
        return $this->_token;
    }
}
