<?php

namespace WHMCSExpert\mtLibs\models;

use WHMCSExpert as main;
use WHMCSExpert\mtLibs\MySQL\PdoWrapper;

abstract class Repository
{
    protected $_filters = array();
    protected $_limit = null;
    protected $_offest = 0;
    protected $_order = array();

    abstract function getModelClass();

    public function __construct($columns = array(), $search = array())
    {
        if (!empty($columns)) {
            $this->columns = $columns;
        }

        if (!empty($search)) {
            $this->search = $search;
        }
    }

    public function fieldDeclaration()
    {
        return forward_static_call(array($this->getModelClass(),'fieldDeclaration'));
    }

    function getPropertyColumn($property)
    {
        return forward_static_call(array($this->getModelClass(),'getPropertyColumn'), $property);
    }

    public function tableName()
    {
        return forward_static_call(array($this->getModelClass(),'tableName'));
    }

    public function limit($limit)
    {
        $this->_limit = $limit;
    }

    public function offset($offset)
    {
        $this->_offest = $offset;
    }

    public function sortBy($field, $vect)
    {
        $column = forward_static_call(array($this->getModelClass(),'getPropertyColumn'), $field);
        $this->_order[$column] = $vect;
    }

    /**
     *
     * @return orm
     */
    function get()
    {
        $result = main\mtLibs\MySQL\Query::select(
            self::fieldDeclaration(),
            self::tableName(),
            $this->_filters,
            $this->_order,
            $this->_limit,
            $this->_offest
        );

        $output = array();

        $class = $this->getModelClass();

        while ($row = $result->fetch()) {
            $output[] = new $class($row['id'], $row);
        }

        return $output;
    }

    function count()
    {
        $fields = $this->fieldDeclaration();
        $first = key($fields);

        if (is_numeric($first)) {
            $first = $fields[$first];
        }
        return main\mtLibs\MySQL\Query::count(
            $first,
            $this->tableName(),
            $this->_filters,
            array(),
            $this->_limit,
            $this->_offest
        );
    }


    /**
     *
     * @param array $ids
     * @return main\mtLibs\models\Repository
     */
    public function idIn(array $ids)
    {

        foreach ($ids as &$id) {
            $id = (int) $id;
        }

        if (!empty($ids)) {
            $this->_filters['id'] = $ids;
        }

        return $this;
    }

    /**
     *
     * @return Repository
     */
    public function resetFilters()
    {
        $this->_filters = array();
        $this->_order = array();
        $this->_limit = null;
        return $this;
    }

    /**
     *
     * @return orm
     * @throws main\mtLibs\exceptions\System
     */
    public function fetchOne()
    {

        $result = main\mtLibs\MySQL\Query::select(
            self::fieldDeclaration(),
            self::tableName(),
            $this->_filters,
            $this->_order,
            1,
            0
        );

        $class = $this->getModelClass();
        $row = $result->fetch();
        if (empty($row)) {
            $criteria = array();
            foreach ($this->_filters as $k => $v) {
                $criteria[] = "{$k}: $v";
            }
            $criteria = implode(", ", $criteria);
            throw new main\mtLibs\exceptions\System("Unable to find '{$class}' with criteria: ({$criteria}) ");
        }

        return new $class($row['id'], $row);
    }

    public function setSearch($search)
    {
        if (!$search) {
            return;
        }
        $search = main\mtLibs\MySQL\PdoWrapper::realEscapeString($search);
        $filter = array();
        foreach ($this->search as $value) {
            $value = str_replace('?', $search, $value);
            $filter[] = "  $value ";
        }
        if (empty($filter)) {
            return false;
        }
        $sql = implode("OR", $filter);
        if ($sql) {
            $this->_filters[] = ' (' . $sql . ') ';
        }
    }
}
