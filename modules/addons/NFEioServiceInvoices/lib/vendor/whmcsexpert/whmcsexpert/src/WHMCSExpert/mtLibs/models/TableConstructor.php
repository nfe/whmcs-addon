<?php

namespace WHMCSExpert\mtLibs\models;

use WHMCSExpert as main;

class TableConstructor
{
    private $prefix;
    private $engine = 'InnoDB';
    private $charset = 'UTF8';
    private $references = array();
    private $mainNameSpace = null;
    private $existsTables = array();
    private $declaredTables = array();

    static $predefinedColumnTypes = array(
        'id' => array(
            'definition' => 'int(:int:) NOT NULL AUTO_INCREMENT'
        , 'default' => array(
                'int' => 11
            , 'isPrimaryKey' => true
            )
        )
    , 'varchar' => array(
            'definition' => 'varchar(:varchar:) CHARACTER SET :charset: :null:'
        , 'default' => array(
                'varchar' => 128
            , 'charset' => 'utf8'
            , 'null' => false
            )
        )
    , 'custom' => array(
            'definition' => ':custom:'
        )
    , 'mediumblob' => array(
            'definition' => 'mediumblob :null:'
        , 'default' => array(
                'null' => false
            )
        )
    , 'smallint' => array(
            'definition' => 'smallint(:smallint:) :null:'
        , 'default' => array(
                'smallint' => 6
            , 'null' => false
            )
        )
    , 'tinyint' => array(
            'definition' => 'tinyint(:tinyint:) :null:'
        , 'default' => array(
                'tinyint' => 6
            , 'null' => false
            )
        )
    , 'boolean' => array(
            'definition' => 'tinyint(:tinyint:) :null:'
        , 'default' => array(
                'tinyint' => 1
            , 'null' => false
            )
        )
    , 'datetime' => array(
            'definition' => 'datetime DEFAULT :default:'
        , 'default' => array(
                'default' => 'NULL'
            )
        )
    , 'date' => array(
            'definition' => 'date DEFAULT :default:'
        , 'default' => array(
                'default' => 'NULL'
            )
        )
    , 'int' => array(
            'definition' => 'int(:int:) :null:'
        , 'default' => array(
                'int' => 11
            , 'null' => false
            )
        )
    , 'mediumtext' => array(
            'definition' => 'mediumtext :null:'
        , 'default' => array(
                'null' => false
            )
        )
    , 'text' => array(
            'definition' => 'text :null:'
        , 'default' => array(
                'null' => false
            )
        )
    );

    function __construct($namespace, $prefix)
    {
        $this->mainNameSpace = $namespace;
        $this->prefix = $prefix;

        $result = main\mtLibs\MySQL\Query::query("SHOW Tables");
        while ($column = $result->fetchColumn()) {
            $this->existsTables[] = $column;
        }
    }

    function createDBModels($models)
    {
        foreach ($models as $model) {
            $class = $this->mainNameSpace . "\\" . $model;

            if (!class_exists($class)) {
                throw new main\mtLibs\exceptions\System('Model Class Not Exists');
            }

            $structure = $class::getTableStructure();

            $this->createTable($structure);
        }

        $this->createReferences();
    }

    function createTable($structure)
    {

        if (isset($structure['preventUpdate'])) {
            return;
        }

        if (empty($structure['name'])) {
            throw new main\mtLibs\exceptions\System('Table name is empty');
        }

        if (in_array($structure['name'], $this->declaredTables) && !isset($structure['multipleUsage'])) {
            throw new main\mtLibs\exceptions\System('Table declared in other model');
        }

        $this->declaredTables[] = $structure['name'];

        $tableName = empty($structure['prefixed']) ? $structure['name'] : $this->prefix . $structure['name'];

        $charset = empty($structure['charset']) ? $this->charset : $structure['charset'];

        $engine = empty($structure['engine']) ? $this->engine : $structure['engine'];

        $columns = array();
        $keys = array(
            'keys' => array()
        , 'primary' => null
        );

        $existsColumns = array();
        $addNewColumns = array();
        $updateColumns = array();

        if (in_array($tableName, $this->existsTables)) {
            $result = main\mtLibs\MySQL\Query::query("SHOW COLUMNS IN `$tableName`");

            while ($row = $result->fetch()) {
                $existsColumns[$row['Field']] = $row;
            }
        }

        foreach ($structure['columns'] as $data) {
            $type = null;
            $options = array();
            foreach ($data as $name => $value) {
                $options[] = "$name=$value";
                if (isset(self::$predefinedColumnTypes[$name])) {
                    $type = $name;
                }
            }

            if ($type == null) {
                throw new main\mtLibs\exceptions\System('Unable to find provided column type: (' . implode(',', $options) . ')');
            }

            $config = self::$predefinedColumnTypes[$type]['default'];

            $config['charset'] = $charset;

            foreach ($data as $name => $value) {
                if ($value === true && isset($config[$name]) && is_numeric($config[$name])) {
                    // yyy to po cos mialo byc
                } elseif ($value) {
                    $config[$name] = $value;
                }
            }

            $definition = self::$predefinedColumnTypes[$type]['definition'];

            $isNull = isset($config['null']) ? $config['null'] : false;

            $config['null'] = ($isNull) ? 'DEFAULT NULL' : 'NOT NULL';

            foreach ($config as $name => $value) {
                $definition = str_replace(':' . $name . ':', $value, $definition);
            }

            $definition = str_replace('^comma^', ',', $definition);

            if (!empty($config['isPrimaryKey'])) {
                $keys['primary'] = $config['name'];
            }

            if (!empty($config['isKey'])) {
                $keys['keys'][] = $config['name'];
            }

            if (!empty($config['uniqueKey'])) {
                $keys['uniqueKey'][] = $config['name'];
            }

            if (!empty($config['unique'])) {
                $keys['unique'][] = $config['name'];
            }

            if (!empty($config['refrence'])) {
                if (!isset($this->references[$tableName])) {
                    $this->references[$tableName] = array();
                }

                $this->references[$tableName][] = array(
                    'column' => $config['name']
                , 'refrence' => $config['refrence']
                , 'ondelete' => (empty($config['ondelete'])) ? 'CASCADE' : $config['ondelete']
                , 'onupdate' => (empty($config['onupdate'])) ? 'NO ACTION' : $config['onupdate']
                );
            }

            if (isset($existsColumns[$config['name']])) {
                if (
                    strpos($definition, $existsColumns[$config['name']]['Type']) === false
                    || ($isNull && $existsColumns[$config['name']]['Type'] == 'YES')
                    || (!$isNull && $existsColumns[$config['name']]['Type'] == 'NO')
                ) {
                    $updateColumns[$config['name']] = '`' . $config['name'] . '` ' . $definition;
                }
            } else {
                $addNewColumns[] = '`' . $config['name'] . '` ' . $definition;
            }
        }

        if (!in_array($tableName, $this->existsTables)) {
            if (!empty($keys['primary'])) {
                $addNewColumns[] = 'PRIMARY KEY (`' . $keys['primary'] . '`)';
            }

            if (!empty($keys['keys'])) {
                foreach ($keys['keys'] as $key) {
                    $addNewColumns[] = 'KEY `' . $key . '` (`' . $key . '`)';
                }
            }

            if (!empty($keys['unique'])) {
                foreach ($keys['unique'] as $key) {
                    $addNewColumns[] = 'UNIQUE KEY `' . $key . '` (`' . $key . '`)';
                }
            }

            if (!empty($keys['uniqueKey'])) {
                $addNewColumns[] = 'UNIQUE `unique_' . implode('_', $keys['uniqueKey']) . '` (`' . implode('`,`', $keys['uniqueKey']) . '`)';
            }
        }

        if (in_array($tableName, $this->existsTables)) {
            foreach ($updateColumns as $column => $definition) {
                $sql = "ALTER TABLE `$tableName` CHANGE `$column` $definition";
                main\mtLibs\MySQL\Query::query($sql);
            }

            foreach ($addNewColumns as $definition) {
                $sql = "ALTER TABLE `$tableName`  ADD $definition";
                main\mtLibs\MySQL\Query::query($sql);
            }
        } else {
            $sql = 'CREATE TABLE `' . $tableName . '` (';
            $sql .= implode(",\n", $addNewColumns);
            $sql .= ') ENGINE=' . $engine . ' DEFAULT CHARSET=' . $charset;

            main\mtLibs\MySQL\Query::query($sql);
        }
    }

    function createReferences()
    {
        foreach ($this->references as $table => $refrences) {
            $result = main\mtLibs\MySQL\Query::query("
                SHOW CREATE TABLE `" . $table . "`");

            $row = $result->fetch();

            $struct = $row['Create Table'];

            foreach ($refrences as $id => $refrence) {
                $refName = $table . '_ibfk_' . ($id + 1);
                $column = $refrence['column'];

                list($model, $refProperty) = explode('::', $refrence['refrence']);

                $class = $this->mainNameSpace . "\\" . $model;
                $refColumn = $class::getProperyColumn($refProperty);
                $refTable = $class::tableName();

                if (strpos($struct, $refName)) {
                    $sql = "
                        ALTER TABLE 
                            `" . $table . "` 
                        DROP FOREIGN KEY `$refName`;";

                    main\mtLibs\MySQL\Query::query($sql);
                }

                $sql = "ALTER TABLE 
                            `$table`
                        ADD CONSTRAINT 
                            `$refName` 
                        FOREIGN KEY 
                            (`$column`) 
                        REFERENCES 
                            `$refTable` (`$refColumn`) 
                                ON DELETE " . $refrence['ondelete'] . " 
                                ON UPDATE " . $refrence['onupdate'] . " ;
                    
                ";

                main\mtLibs\MySQL\Query::query($sql);
            }
        }
    }

    function dropModels($models)
    {
        foreach ($models as $model) {
            $class = $this->mainNameSpace . "\\" . $model;
            $structure = $class::getTableStructure();

            $sql = "select COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_COLUMN_NAME, REFERENCED_TABLE_NAME
                    from information_schema.KEY_COLUMN_USAGE
                    where TABLE_NAME = 'table to be checked';";

        }

    }
}