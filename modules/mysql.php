<?php
class MYSQL {
    private $database = 'munchkin'; // База данных
    private $host = 'localhost';    // Хост
    private $username = 'root';     // Имя пользователя
    private $password = 'root';     // Пароль
    public $connection;             // Соединение с базой данных (public для доступа из других файлов)

    public $queries = 0; // Количество запросов
    public $timems = 0;  // Время выполнения запросов

    // Конструктор: установка соединения и кодировки
    function __construct() {
        // Подключаемся к базе данных
        $this->connection = mysqli_connect($this->host, $this->username, $this->password, $this->database);
        // Проверяем успешность подключения
        if (!$this->connection) {
            die('<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8" /></head><body><h1>Database connection error: ' . mysqli_connect_error() . '</h1></body></html>');
        }
        // Устанавливаем кодировку
        if (!mysqli_query($this->connection, "SET NAMES 'utf8'")) {
            die('Error setting UTF-8 encoding: ' . mysqli_error($this->connection));
        }
    }

    // Базовый запрос к базе
    function q_base($str_query) {
        $start = microtime(true);
        $this->queries++;
        $result = mysqli_query($this->connection, $str_query);
        if (!$result) {
            die('MySQL error: "' . mysqli_error($this->connection) . '"<br>' . htmlspecialchars($str_query));
        }
        $end = microtime(true);
        $this->timems += ($end - $start);
        return $result;
    }

    // Метод-синоним для sql_query (для совместимости с другими файлами)
    function sql_query($str_query) {
        return $this->q_base($str_query);
    }

    // Деструктор: закрытие соединения
    function __destruct() {
        if ($this->connection) {
            mysqli_close($this->connection);
        }
    }
}
?>