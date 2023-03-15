<?php

namespace WHMCSExpert\mtLibs\exceptions;

/**
 * Use for general module errors
 *
 */
class Validation extends System
{
    private $fields = array();
    public function __construct($message, array $fields = array())
    {
        $this->fields = $fields;
        parent::__construct($message);
    }

    function getFields()
    {
        return $this->fields;
    }
}
