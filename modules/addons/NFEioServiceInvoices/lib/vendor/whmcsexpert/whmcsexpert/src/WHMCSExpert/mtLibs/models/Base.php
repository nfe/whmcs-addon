<?php

namespace WHMCSExpert\mtLibs\models;

use WHMCSExpert as main;

abstract class Base
{
    /**
     * Normalized Time Stamp
     *
     * @param string $strTime
     * @return string
     */
    static function timeStamp($strTime = 'now')
    {
        return date('Y-m-d H:i:s', strtotime($strTime));
    }

    /**
     * Disable Get Function
     *
     * @param string $property
     * @throws main\mtLibs\exceptions\System
     */
    function __get($property)
    {
        throw new main\mtLibs\exceptions\System('Property: ' . $property . ' does not exits in: ' . get_called_class(), main\mtLibs\exceptions\Codes::PROPERTY_NOT_EXISTS);
    }

    /**
     * Disable Set Function
     *
     * @param string $property
     * @param string $value
     * @throws main\mtLibs\exceptions\System
     */
    function __set($property, $value)
    {
        throw new main\mtLibs\exceptions\System('Property: ' . $property . ' does not exits in: ' . get_called_class(), main\mtLibs\exceptions\Codes::PROPERTY_NOT_EXISTS);
    }

    /**
     * Disable Call Function
     *
     * @param string $function
     * @param string $params
     * @throws main\mtLibs\exceptions\System
     */
    function __call($function, $params)
    {
        throw new main\mtLibs\exceptions\System('Function: ' . $function . ' does not exits in: ' . get_called_class(), main\mtLibs\exceptions\Codes::PROPERTY_NOT_EXISTS);
    }

    /**
     * Cast To array
     *
     * @param string $container
     * @return array
     */
    function toArray($container = true)
    {
        $className = get_called_class();

        $fields = get_class_vars($className);

        foreach (explode('\\', $className) as $className) {
        }

        $data = array();

        foreach ($fields as $name => $defult) {
            if (isset($this->{$name})) {
                $data[$name] = $this->{$name};
            }
        }

        if ($container === true) {
            return array(
                $className => $data
            );
        } elseif ($container) {
            return array(
                $container => $data
            );
        } else {
            return $data;
        }
    }


    function serialize($input)
    {
        return base64_encode(serialize($input));
    }

    function unserialize($input)
    {
        return unserialize(base64_decode($input));
    }
}
