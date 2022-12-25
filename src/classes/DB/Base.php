<?php

namespace DB;

class Base
{
    protected static self $instance;
    protected static string $dbName;
    protected $connection;

    protected function __construct(string $host, string $dbName, string $dbUser, string $dbPassword)
    {
        self::$dbName = $dbName;
        $this->connection = new \PDO("mysql:host=$host;dbname=$dbName;charset=utf8", $dbUser, $dbPassword);
        $this->connection->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Создание подключения к БД
     * @param string $host
     * @param string $dbName
     * @param string $dbUser
     * @param string $dbPassword
     * @return static
     */
    public static function init(string $host, string $dbName, string $dbUser, string $dbPassword): self
    {
        if (!isset(static::$instance)) {
            static::$instance = new static($host, $dbName, $dbUser, $dbPassword);
        }

        return static::$instance;
    }

    /** Закрытие подключения к БД
     * @return void
     */
    public static function close(): void
    {
        if (isset(static::$instance)) {
            static::$instance->connection = null;
        }
    }

    /** Получение данных из базы
     * @param string $tableName
     * @param array $fields массив полей выбираемых из базы данных
     * @param array|null $filter ассоциативный массив фильтра выборки ['поле' => 'значение']
     * @param bool $orFilter флаг переключения фильтра на OR вместо AND
     * @return false|\PDOStatement
     * @throws \PDOException|\Exception
     */
    public static function select(string $tableName, array $fields, array $filter = null, bool $orFilter = false)
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

        $dbResult = $instance->connection->query($sqlString);
        $dbResult->setFetchMode(\PDO::FETCH_ASSOC);

        return $dbResult;
    }

    /** Добавление строки в базу данных
     * @param string $tableName
     * @param array $fields ассоциативный массив добавляемых данных ['поле' => 'значение']
     * @return bool
     * @throws \Exception
     */
    public static function add(string $tableName, array $fields): bool
    {
        if (!isset(self::$instance))
            throw new \Exception("Не заданы параметры подключения к базе данных.");

        $instance = self::$instance;
        $sqlString = "INSERT INTO $tableName(" . implode(",", array_keys($fields)) . ") VALUES ("
            . implode(",", array_map(fn($value) => "'$value'", array_values($fields))) . ");";

        return $instance->connection->prepare($sqlString)->execute();
    }

    /** Обновление строки в базе данных
     * @param string $tableName
     * @param array $fields ассоциативный массив добавляемых данных ['поле' => 'значение']
     * @param array|null $filter ассоциативный массив фильтра выборки для обновления ['поле' => 'значение']
     * @return bool
     * @throws \Exception
     */
    public static function update(string $tableName, array $fields, array $filter = null): bool
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

    /** Создание таблицы в базе данных при её отсутствии
     * @param string $tableName
     * @param array $fields ассоциативный массив полей таблицы ['название поля' => 'тип и аттрибуты']
     * @return bool
     * @throws \PDOException|\Exception
     */
    public static function createTable(string $tableName, array $fields): bool
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