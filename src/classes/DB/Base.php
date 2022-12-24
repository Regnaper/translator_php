<?php

namespace DB;

class Base
{
    protected static self $instance;
    protected static string $dbName;
    protected object $connection;

    protected function __construct(string $host, string $dbName, string $dbUser, string $dbPassword)
    {
        self::$dbName = $dbName;
        $this->connection = new \PDO("mysql:host=$host;dbname=$dbName;charset=utf8", $dbUser, $dbPassword);
    }

    public static function init(string $host, string $dbName, string $dbUser, string $dbPassword): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($host, $dbName, $dbUser, $dbPassword);
        }

        return self::$instance;
    }

    public static function select(string $tableName, array $fields, array $filter = null, $orFilter = false)
    {
        if (!isset(self::$instance))
            throw new \Exception("Не заданы параметры подключения к базе данных.");

        $instance = self::$instance;
        $sqlString = "SELECT " . implode(",", $fields) . " FROM " . $tableName;

        if (!empty($filter)) {
            $sqlString .= " WHERE ";
            $filterRows = [];
            foreach ($filter as $filterKey => $filterValue) {
                $filterRows[] = "$filterKey='$filterValue'";
            }
            $sqlString .= implode($orFilter ? " OR " : " AND ", $filterRows);
        }
        $sqlString .= ";";

        return $instance->connection->query($sqlString);
    }

    public static function add(string $tableName, array $fields)
    {
        if (!isset(self::$instance))
            throw new \Exception("Не заданы параметры подключения к базе данных.");

        $instance = self::$instance;
        $sqlString = "INSERT INTO $tableName(" . implode(",", array_keys($fields)) . ") VALUES ("
            . implode(",", array_map(fn($value) => "'$value'", array_values($fields))) . ");";

        return $instance->connection->prepare($sqlString)->execute();
    }

    public static function update(string $tableName, array $fields, array $filter = null)
    {
        if (!isset(self::$instance))
            throw new \Exception("Не заданы параметры подключения к базе данных.");

        $instance = self::$instance;
        $sqlString = "UPDATE $tableName SET ";

        $fieldsRows = [];
        foreach ($fields as $fieldKey => $fieldValue) {
            $fieldsRows[] = "$fieldKey='$fieldValue'";
        }
        $sqlString .= implode(",", $fieldsRows);

        if (!empty($filter)) {
            $sqlString .= " WHERE ";
            $filterRows = [];
            foreach ($filter as $filterKey => $filterValue) {
                $filterRows[] = "$filterKey=$filterValue";
            }
            $sqlString .= implode(" AND ", $filterRows);
        }
        $sqlString .= ";";

        return $instance->connection->prepare($sqlString)->execute();
    }

    public static function createTable(string $tableName, array $fields)
    {
        if (!isset(self::$instance))
            throw new \Exception("Не заданы параметры подключения к базе данных.");

        $instance = self::$instance;

        $dbResult = $instance->connection->query("SHOW TABLES FROM " . self::$dbName . " LIKE '$tableName';");
        if ($dbResult && $dbResult->Fetch())
            return true;

        $sqlString = "CREATE TABLE $tableName (id INT PRIMARY KEY AUTO_INCREMENT,";

        $fieldsRows = [];
        foreach ($fields as $fieldKey => $fieldType) {
            $fieldsRows[] = "$fieldKey $fieldType";
        }
        $sqlString .= implode(",", $fieldsRows);
        $sqlString .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COLLATE='utf8_general_ci';";

        return $instance->connection->prepare($sqlString)->execute();
    }
}