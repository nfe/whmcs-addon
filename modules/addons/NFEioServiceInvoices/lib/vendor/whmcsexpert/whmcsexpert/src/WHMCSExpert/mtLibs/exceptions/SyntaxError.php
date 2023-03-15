<?php

namespace WHMCSExpert\mtLibs\exceptions;

/**
 * Used in Error Handler
 *
 */
class SyntaxError extends System
{
    private $_type;
    private $_line;
    private $_file;
    public function __construct($message, $type, $code, $line, $file)
    {
        $this->_type = $type;
        $this->_code = $code;
        $this->_line = $line;
        $this->_file = $file;
        parent::__construct($message, $code);
    }
}
